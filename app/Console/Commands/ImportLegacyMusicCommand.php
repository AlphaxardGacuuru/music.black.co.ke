<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Moves the old music.black.co.ke data (integer ids, rows linked by
 * username) onto the new schema (UUIDs, rows linked by user id).
 *
 * The old database is read through the --from connection ("legacy" by
 * default) and left untouched. The --to connection (the app's default by
 * default) must be freshly migrated and empty: run this before any seeders. New ids are UUIDv7s stamped with each row's original
 * created_at, so lists ordered by id keep their old order. Rows whose user,
 * song or parent no longer exists are skipped and counted.
 */
class ImportLegacyMusicCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'music:import-legacy
        {--from=legacy : Connection holding the old music.black.co.ke database}
        {--to= : Connection to import into (defaults to the default connection)}
        {--chunk=500 : Rows read from the old database at a time}
        {--force : Run in production without asking}';

    protected $description = 'Import the old music.black.co.ke database into the new schema';

    private Connection $legacy;

    private Connection $target;

    /**
     * Old id => new UUID, per old table.
     *
     * @var array<string, array<int|string, string>>
     */
    private array $ids = [];

    /**
     * Old username => new user UUID.
     *
     * @var array<string, string>
     */
    private array $userIds = [];

    /**
     * @var array<int, array{0: string, 1: int, 2: int, 3: int}>
     */
    private array $report = [];

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $from = (string) $this->option('from');
        $to = (string) ($this->option('to') ?: config('database.default'));

        if ($from === $to) {
            $this->error('--from and --to must be different connections.');

            return self::FAILURE;
        }

        $this->legacy = DB::connection($from);
        $this->target = DB::connection($to);

        if (! $this->legacy->getSchemaBuilder()->hasTable('users')) {
            $this->error("The [{$from}] database has no users table. Check its settings (LEGACY_DB_* for the default legacy connection).");

            return self::FAILURE;
        }

        if ($this->target->table('users')->exists()) {
            $this->error("The [{$to}] database already has users. Run this right after `php artisan migrate:fresh`, before any seeders.");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importing [%s] %s into [%s] %s.',
            $from,
            $this->legacy->getDatabaseName(),
            $to,
            $this->target->getDatabaseName(),
        ));

        foreach ($this->steps() as [$from, $to, $map, $ignoreDuplicates]) {
            $this->importTable($from, $to, $map, $ignoreDuplicates);
        }

        // users.decos counts the decos a fan holds (the old column was never
        // kept up to date).
        $this->target->table('users')->update([
            'decos' => DB::raw('(select count(*) from decos where decos.user_id = users.id)'),
        ]);

        $this->table(['Old table', 'Read', 'Imported', 'Skipped'], $this->report);
        $this->info('Import finished. Now run the RoleSeeder and AdminUserSeeder.');

        return self::SUCCESS;
    }

    /**
     * Every table to copy, parents before children.
     *
     * @return array<int, array{0: string, 1: string, 2: Closure(object): ?array<string, mixed>, 3: bool}>
     */
    private function steps(): array
    {
        return [
            ['users', 'users', fn(object $row) => [
                'name' => $row->name ?: ltrim($row->username, '@'),
                'username' => $row->username,
                'email' => $row->email,
                'email_verified_at' => $row->email_verified_at,
                // OLD set the password to the phone number at signup, so
                // users can still sign in with their email and phone number.
                'password' => $row->password ?: Hash::make(Str::random(40)),
                'phone' => $row->phone,
                // Same normalisation as the User model's saving hook.
                'hashed_phone' => $row->phone ? hash('sha256', substr_replace($row->phone, '254', 0, -9)) : null,
                'gender' => $row->gender,
                'account_type' => $row->account_type ?: 'normal',
                'avatar' => $row->avatar ?: 'avatars/male-avatar.png',
                'backdrop' => $row->backdrop ?: 'img/headphones.jpg',
                'bio' => $row->bio,
                'dob' => $row->dob,
                'location' => $row->location,
                'withdrawal' => $row->withdrawal ?: '1000',
            ], false],

            ['follows', 'follows', fn(object $row) => $this->withUsers([
                'followed_id' => $this->user($row->followed),
                'user_id' => $this->user($row->username),
                'muted' => $row->muted ?? json_encode(['posts' => false, 'stories' => false]),
                'blocked' => $row->blocked ?: null,
            ]), true],

            ['audio_albums', 'audio_albums', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'name' => $row->name,
                'cover' => $row->cover,
                'released' => $row->released,
            ]), false],

            ['audios', 'audios', fn(object $row) => $this->withUsers([
                'audio' => $row->audio,
                'name' => $row->name,
                'user_id' => $this->user($row->username),
                'ft_id' => $row->ft ? $this->user($row->ft) : null,
                'audio_album_id' => $this->id('audio_albums', $row->audio_album_id),
                'genre' => $row->genre,
                'thumbnail' => $row->thumbnail,
                'description' => $row->description,
                'released' => $row->released,
            ], required: ['user_id', 'audio_album_id']), false],

            ['video_albums', 'video_albums', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'name' => $row->name,
                'cover' => $row->cover,
                'released' => $row->released,
            ]), false],

            ['videos', 'videos', fn(object $row) => $this->withUsers([
                'video' => $row->video,
                'name' => $row->name,
                'user_id' => $this->user($row->username),
                'ft_id' => $row->ft ? $this->user($row->ft) : null,
                'video_album_id' => $this->id('video_albums', $row->video_album_id),
                'genre' => $row->genre,
                'thumbnail' => $row->thumbnail,
                'description' => $row->description,
                'released' => $row->released,
            ], required: ['user_id', 'video_album_id']), false],

            ...$this->likesAndComments('audio', 'audios'),
            ...$this->likesAndComments('video', 'videos'),

            ['cart_audios', 'cart_audios', fn(object $row) => $this->withUsers([
                'audio_id' => $this->id('audios', $row->audio_id),
                'user_id' => $this->user($row->username),
            ]), true],

            ['cart_videos', 'cart_videos', fn(object $row) => $this->withUsers([
                'video_id' => $this->id('videos', $row->video_id),
                'user_id' => $this->user($row->username),
            ]), true],

            ['bought_audios', 'bought_audios', fn(object $row) => $this->withUsers([
                'audio_id' => $this->id('audios', $row->audio_id),
                'user_id' => $this->user($row->username),
                'artist_id' => $this->user($row->artist),
                'price' => $row->price,
            ]), true],

            ['bought_videos', 'bought_videos', fn(object $row) => $this->withUsers([
                'video_id' => $this->id('videos', $row->video_id),
                'user_id' => $this->user($row->username),
                'artist_id' => $this->user($row->artist),
                'price' => $row->price,
            ]), true],

            ['karaokes', 'karaokes', fn(object $row) => $this->withUsers([
                'karaoke' => $row->karaoke,
                'user_id' => $this->user($row->username),
                'audio_id' => $this->id('audios', $row->audio_id),
                'description' => $row->description,
            ]), false],

            ['karaoke_audios', 'karaoke_audios', fn(object $row) => $this->withUsers([
                'audio_id' => $this->id('audios', $row->audio_id),
                'user_id' => $this->user($row->username),
            ]), false],

            ...$this->likesAndComments('karaoke', 'karaokes'),

            ['saved_karaokes', 'saved_karaokes', fn(object $row) => $this->withUsers([
                'karaoke_id' => $this->id('karaokes', $row->karaoke_id),
                'user_id' => $this->user($row->username),
            ]), true],

            ['posts', 'posts', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'text' => $row->text,
                'media' => $row->media,
                'parameter_1' => $row->parameter_1,
                'parameter_2' => $row->parameter_2,
                'parameter_3' => $row->parameter_3,
                'parameter_4' => $row->parameter_4,
                'parameter_5' => $row->parameter_5,
            ]), false],

            ...$this->likesAndComments('post', 'posts', commentMedia: true),

            ['polls', 'polls', fn(object $row) => $this->withUsers([
                'post_id' => $this->id('posts', $row->post_id),
                'user_id' => $this->user($row->username),
                'parameter' => $row->parameter,
            ]), true],

            ['stories', 'stories', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'text' => $row->text,
                'media' => $row->media,
            ]), false],

            ['seen_stories', 'seen_stories', fn(object $row) => $this->withUsers([
                'story_id' => $this->id('stories', $row->story_id),
                'user_id' => $this->user($row->username),
                'seen_at' => $row->seen_at,
            ]), false],

            ['chats', 'chats', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'to_id' => $this->user($row->to),
                'text' => $row->text,
                'media' => $row->media,
                'deleted_at' => $row->deleted_at,
                'seen_at' => $row->seen_at,
            ]), false],

            ['decos', 'decos', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'artist_id' => $this->user($row->artist),
            ]), false],

            // Historical payouts were sent outside the app, so they count as paid.
            ['song_payouts', 'song_payouts', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'amount' => $row->amount,
                'fee' => 0,
                'paid_at' => $row->created_at,
            ]), false],

            // Wallet top-ups. Payments with no matching user can't count
            // towards anyone's balance, so they're skipped.
            ['kopokopos', 'mpesa_transactions', fn(object $row) => $this->withUsers([
                'user_id' => $row->username ? $this->user($row->username) : null,
                'kopokopo_id' => $row->kopokopo_id,
                'type' => $row->type,
                'initiation_time' => $row->initiation_time,
                'status' => $row->status,
                'event_type' => $row->event_type,
                'resource_id' => $row->resource_id,
                'reference' => $row->reference,
                'origination_time' => $row->origination_time,
                'sender_phone_number' => $row->sender_phone_number,
                'amount' => $row->amount,
                'currency' => $row->currency,
                'till_number' => $row->till_number,
                'system' => $row->system,
                'resource_status' => $row->resource_status,
                'sender_first_name' => $row->sender_first_name,
                'sender_middle_name' => $row->sender_middle_name,
                'sender_last_name' => $row->sender_last_name,
            ]), false],

            ['kopokopo_recipients', 'kopokopo_recipients', fn(object $row) => $this->withUsers([
                'user_id' => $row->username ? $this->user($row->username) : null,
                'destination_reference' => $row->destination_reference,
                'type' => 'mobile_wallet',
            ]), false],

            ['searches', 'searches', fn(object $row) => $this->withUsers([
                'user_id' => $this->user($row->username),
                'keyword' => $row->keyword,
            ]), false],
        ];
    }

    /**
     * The likes, comments and comment likes of a video, audio, karaoke or
     * post (e.g. "audio" => audio_likes, audio_comments, audio_comment_likes).
     *
     * @return array<int, array{0: string, 1: string, 2: Closure(object): ?array<string, mixed>, 3: bool}>
     */
    private function likesAndComments(string $kind, string $parentTable, bool $commentMedia = false): array
    {
        $parentKey = "{$kind}_id";
        $commentKey = "{$kind}_comment_id";

        return [
            ["{$kind}_likes", "{$kind}_likes", fn(object $row) => $this->withUsers([
                $parentKey => $this->id($parentTable, $row->{$parentKey}),
                'user_id' => $this->user($row->username),
            ]), true],

            ["{$kind}_comments", "{$kind}_comments", fn(object $row) => $this->withUsers([
                $parentKey => $this->id($parentTable, $row->{$parentKey}),
                'user_id' => $this->user($row->username),
                'text' => $row->text ?? '',
                ...($commentMedia ? ['media' => $row->media] : []),
            ]), false],

            ["{$kind}_comment_likes", "{$kind}_comment_likes", fn(object $row) => $this->withUsers([
                $commentKey => $this->id("{$kind}_comments", $row->{$commentKey}),
                'user_id' => $this->user($row->username),
            ]), true],
        ];
    }

    /**
     * Copy one old table into its new one, remembering each row's new id.
     *
     * @param  Closure(object): ?array<string, mixed>  $map  The new row, or null to skip it.
     */
    private function importTable(string $from, string $to, Closure $map, bool $ignoreDuplicates): void
    {
        if (! $this->legacy->getSchemaBuilder()->hasTable($from)) {
            $this->report[] = [$from, 0, 0, 0];

            return;
        }

        $read = 0;
        $imported = 0;
        $previousCreatedAt = null;

        $this->legacy->table($from)
            ->orderBy('id')
            ->chunk((int) $this->option('chunk'), function ($rows) use ($from, $to, $map, $ignoreDuplicates, &$read, &$imported, &$previousCreatedAt) {
                $batch = [];

                foreach ($rows as $raw) {
                    $read++;
                    $row = $this->lenient($raw);
                    $mapped = $map($row);

                    if ($mapped === null) {
                        continue;
                    }

                    // A row with no usable created_at (null or MySQL's zero
                    // date) takes the previous row's, so its new id still
                    // sorts where its old id did.
                    $createdAt = $row->created_at ?? $previousCreatedAt ?? $row->updated_at ?? now();
                    $previousCreatedAt = $createdAt;
                    $newId = (string) Str::uuid7(Carbon::parse($createdAt));

                    $this->ids[$from][$row->id] = $newId;

                    if ($from === 'users') {
                        $this->userIds[$row->username] = $newId;
                    }

                    $batch[] = [
                        'id' => $newId,
                        ...$mapped,
                        'created_at' => $createdAt,
                        'updated_at' => $row->updated_at ?? $createdAt,
                    ];
                }

                if ($batch === []) {
                    return;
                }

                if ($ignoreDuplicates) {
                    $imported += $this->target->table($to)->insertOrIgnore($batch);
                } else {
                    $this->target->table($to)->insert($batch);
                    $imported += count($batch);
                }
            });

        $this->report[] = [$from, $read, $imported, $read - $imported];
        $this->line(sprintf('  %s: %d of %d rows', $from, $imported, $read));
    }

    /**
     * Null (skip the row) when any of the required user or parent links
     * couldn't be resolved, otherwise the row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>|null  $required  Keys that must resolve; defaults to every "*_id" link except the optional ones.
     * @return array<string, mixed>|null
     */
    private function withUsers(array $row, ?array $required = null): ?array
    {
        // ft_id is an optional link; kopokopo_id and resource_id are
        // Kopokopo's own references, not links.
        $notLinks = ['ft_id', 'kopokopo_id', 'resource_id'];

        $required ??= array_filter(
            array_keys($row),
            fn(string $key) => str_ends_with($key, '_id') && ! in_array($key, $notLinks, true)
        );

        foreach ($required as $key) {
            if (($row[$key] ?? null) === null) {
                return null;
            }
        }

        return $row;
    }

    /**
     * The production schema drifted from the old migrations over the years
     * (columns like chats.seen_at or users.decos exist in some copies and
     * not others), so a column that isn't there reads as null instead of
     * failing the import. MySQL's zero dates ("0000-00-00 00:00:00") also
     * read as null, since they aren't real dates and strict mode won't
     * accept them on insert.
     */
    private function lenient(object $row): object
    {
        return new class((array) $row)
        {
            /**
             * @param  array<string, mixed>  $columns
             */
            public function __construct(private array $columns) {}

            public function __get(string $column): mixed
            {
                $value = $this->columns[$column] ?? null;

                return is_string($value) && str_starts_with($value, '0000-00-00') ? null : $value;
            }

            public function __isset(string $column): bool
            {
                return $this->__get($column) !== null;
            }
        };
    }

    private function user(?string $username): ?string
    {
        return $username === null ? null : ($this->userIds[$username] ?? null);
    }

    private function id(string $table, int|string|null $oldId): ?string
    {
        return $oldId === null ? null : ($this->ids[$table][$oldId] ?? null);
    }
}
