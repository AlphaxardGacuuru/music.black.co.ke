<?php

namespace App\Models;

use App\Enums\EmailNotificationCategory;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laragear\TwoFactor\TwoFactorAuthentication;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'settings', 'gender', 'google_id', 'account_type', 'avatar', 'backdrop', 'bio', 'dob', 'location', 'withdrawal', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, HasRoles, HasUuids, Notifiable, TwoFactorAuthentication;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'settings',
        'gender',
        'account_type',
        'avatar',
        'backdrop',
        'bio',
        'dob',
        'location',
        'withdrawal',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'object',
        'email_verified_at' => 'datetime',
        'verified' => 'boolean',
        'updated_at' => 'datetime:d M Y',
        'created_at' => 'datetime:d M Y h:i:s',
    ];

    protected string $guard_name = 'web';

    /**
     * Get the name of the guard associated with the user model.
     */
    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    protected static function booted(): void
    {
        // Every account needs a public "@handle", whichever way it signed up.
        static::creating(function (User $user) {
            if (! $user->username) {
                $user->username = static::uniqueUsernameFor($user->name ?: $user->email);
            }
        });

        // Users follow themselves (so their own posts show in their feed) and
        // the @blackmusic account.
        static::created(function (User $user) {
            $user->follows()->create(['followed_id' => $user->id, 'muted' => ['posts' => false, 'stories' => false]]);

            $blackMusic = static::query()->where('username', '@blackmusic')->first();

            if ($blackMusic && $blackMusic->isNot($user)) {
                $user->follows()->create(['followed_id' => $blackMusic->id, 'muted' => ['posts' => false, 'stories' => false]]);
            }
        });

        static::saving(function (User $user) {
            if ($user->phone && $user->isDirty('phone')) {
                $normalized = substr_replace($user->phone, '254', 0, -9);
                $user->hashed_phone = hash('sha256', $normalized);
            }
        });
    }

    /**
     * Accesors.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn($value) => preg_match("/https/", $value) ? $value : "/storage/" . $value
        );
    }

    protected function backdrop(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "/storage/" . $value
        );
    }

    protected function emailVerifiedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('d M Y') : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->format('d M Y'),
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->format('d M Y h:i:s'),
        );
    }

    /**
     * Other users this user referred, who signed up via their referral link.
     */
    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Who referred this user, if anyone (a user can only be referred once).
     */
    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function audios(): HasMany
    {
        return $this->hasMany(Audio::class);
    }

    public function audioAlbums(): HasMany
    {
        return $this->hasMany(AudioAlbum::class);
    }

    public function audioComments(): HasMany
    {
        return $this->hasMany(AudioComment::class);
    }

    public function audioCommentLikes(): HasMany
    {
        return $this->hasMany(AudioCommentLike::class);
    }

    public function audioLikes(): HasMany
    {
        return $this->hasMany(AudioLike::class);
    }

    public function boughtAudios(): HasMany
    {
        return $this->hasMany(BoughtAudio::class);
    }

    public function boughtVideos(): HasMany
    {
        return $this->hasMany(BoughtVideo::class);
    }

    public function cartAudios(): HasMany
    {
        return $this->hasMany(CartAudio::class);
    }

    public function cartVideos(): HasMany
    {
        return $this->hasMany(CartVideo::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function decorations(): HasMany
    {
        return $this->hasMany(Deco::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function karaokes(): HasMany
    {
        return $this->hasMany(Karaoke::class);
    }

    public function karaokeAudios(): HasMany
    {
        return $this->hasMany(KaraokeAudio::class);
    }

    public function karaokeComments(): HasMany
    {
        return $this->hasMany(KaraokeComment::class);
    }

    public function karaokeCommentLikes(): HasMany
    {
        return $this->hasMany(KaraokeCommentLike::class);
    }

    public function karaokeLikes(): HasMany
    {
        return $this->hasMany(KaraokeLike::class);
    }

    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MPESATransaction::class);
    }

    public function kopokopoRecipients(): HasMany
    {
        return $this->hasMany(KopokopoRecipient::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function postCommentLikes(): HasMany
    {
        return $this->hasMany(PostCommentLike::class);
    }

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function savedKaraokes(): HasMany
    {
        return $this->hasMany(SavedKaraoke::class);
    }

    public function searches(): HasMany
    {
        return $this->hasMany(Search::class);
    }

    public function songPayouts(): HasMany
    {
        return $this->hasMany(SongPayout::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function videoAlbums(): HasMany
    {
        return $this->hasMany(VideoAlbum::class);
    }

    public function videoComments(): HasMany
    {
        return $this->hasMany(VideoComment::class);
    }

    public function videoCommentLikes(): HasMany
    {
        return $this->hasMany(VideoCommentLike::class);
    }

    public function videoLikes(): HasMany
    {
        return $this->hasMany(VideoLike::class);
    }

    /**
     * Sales of this artist's audios.
     */
    public function artistsBoughtAudios(): HasMany
    {
        return $this->hasMany(BoughtAudio::class, 'artist_id');
    }

    /**
     * Sales of this artist's videos.
     */
    public function artistsBoughtVideos(): HasMany
    {
        return $this->hasMany(BoughtVideo::class, 'artist_id');
    }

    /**
     * Follow rows where someone follows this user.
     */
    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'followed_id');
    }

    /*
     * Custom functions
     */

    /**
     * A unique "@handle" built from a display name or email, e.g. "@janedoe"
     * or "@janedoe2" when that one is taken. Usernames max out at 15 chars.
     */
    public static function uniqueUsernameFor(string $source): string
    {
        $base = Str::of(Str::before($source, '@') ?: $source)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->limit(10, '')
            ->toString();

        $base = '@' . ($base !== '' ? $base : 'user');
        $username = $base;
        $suffix = 1;

        while (static::query()->where('username', $username)->exists()) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }

    /**
     * Whether the given user follows this user.
     */
    public function hasFollowed(?string $userId): bool
    {
        return $userId !== null && Follow::query()
            ->where('followed_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * How many people follow this user, not counting themselves.
     */
    public function fans(): int
    {
        return Follow::query()->where('followed_id', $this->id)->count() - 1;
    }

    /**
     * Whether the given user has bought one of this artist's videos.
     */
    public function hasBoughtVideo(?string $userId): bool
    {
        return $this->artistsBoughtVideos->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user has bought one of this artist's audios.
     */
    public function hasBoughtAudio(?string $userId): bool
    {
        return $this->artistsBoughtAudios->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user has bought at least one song from this artist,
     * which is what lets fans follow an artist.
     */
    public function hasBought1(?string $userId): bool
    {
        return $this->hasBoughtVideo($userId) || $this->hasBoughtAudio($userId);
    }

    /**
     * Wallet balance: everything paid in via M-Pesa, less the cost of every
     * song bought (videos are KES 20, audios KES 10).
     */
    public function balance(): int
    {
        $spent = $this->boughtVideos->count() * BoughtVideo::PRICE
            + $this->boughtAudios->count() * BoughtAudio::PRICE;

        return (int) $this->mpesaTransactions->sum('amount') - $spent;
    }

    /**
     * Whether this user still wants emails for the given category — opt-out
     * by default (true) so users created before a category existed, or who
     * never touched their preferences, keep receiving it.
     */
    public function wantsEmail(EmailNotificationCategory $category): bool
    {
        return (bool) ($this->settings?->{$category->settingsKey()} ?? true);
    }

    /**
     * A signed, no-login-required link that turns the given category off —
     * embedded in every email of that category so it can be unsubscribed
     * from with one click.
     */
    public function unsubscribeUrlFor(EmailNotificationCategory $category): string
    {
        return URL::signedRoute('unsubscribe.show', [
            'user' => $this->getKey(),
            'category' => $category->value,
        ]);
    }
}
