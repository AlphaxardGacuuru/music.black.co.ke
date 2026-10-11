<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'text',
        'media',
        'parameter_1',
        'parameter_2',
        'parameter_3',
        'parameter_4',
        'parameter_5',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_edited' => 'boolean',
    ];

    /**
     * Accessors.
     */
    protected function media(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? "/storage/" . $value : $value,
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
            get: fn($value) => Carbon::parse($value)->format('d M Y'),
        );
    }

    /*
     * Relationships
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    /*
     * Custom functions
     */

    /**
     * Whether the given user voted for this parameter (poll option).
     */
    public function hasVoted(?string $userId, string $parameter): bool
    {
        return $this->polls
            ->where('user_id', $userId)
            ->where('parameter', $parameter)
            ->isNotEmpty();
    }

    /**
     * The share of this poll's votes that went to the given parameter, as a
     * percentage rounded to one decimal place.
     */
    public function percentage(string $parameter): float
    {
        $votes = $this->polls->count();
        $parameterVotes = $this->polls->where('parameter', $parameter)->count();

        return round($parameterVotes > 0 ? $parameterVotes / $votes * 100 : 0, 1);
    }

    /**
     * Polls stay open for 24 hours after they're posted.
     */
    public function isWithin24Hrs(): bool
    {
        return Carbon::parse($this->getRawOriginal('created_at'))->gt(Carbon::now()->subDay());
    }

    /**
     * Whether the given user has liked this post.
     */
    public function hasLiked(?string $userId): bool
    {
        return $this->likes->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user follows this post's author.
     */
    public function hasFollowed(?string $userId): bool
    {
        return Follow::query()
            ->where('followed_id', $this->user_id)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Whether the given user has muted posts from this post's author.
     */
    public function hasMuted(?string $userId): bool
    {
        return (bool) Follow::query()
            ->where('followed_id', $this->user_id)
            ->where('user_id', $userId)
            ->first()
            ?->muted['posts'];
    }
}
