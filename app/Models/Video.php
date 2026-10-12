<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'video',
        'name',
        'user_id',
        'ft_id',
        'video_album_id',
        'genre',
        'thumbnail',
        'description',
        'released',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'released' => 'datetime',
    ];

    /**
     * Accessors.
     */
    protected function video(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "storage/" . $value,
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            // Null until ProcessVideoJob makes one, when none was uploaded.
            get: fn($value) => match (true) {
                $value === null => null,
                (bool) preg_match("/http/", $value) => $value,
                default => "/storage/" . $value,
            },
        );
    }

    protected function released(): Attribute
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

    /**
     * The featured artist.
     */
    public function ft(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ft_id');
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(VideoAlbum::class, 'video_album_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(VideoLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(VideoComment::class);
    }

    public function bought(): HasMany
    {
        return $this->hasMany(BoughtVideo::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(CartVideo::class);
    }

    /*
     * Custom functions
     */

    /**
     * Whether the given user has liked this video.
     */
    public function hasLiked(?string $userId): bool
    {
        return $this->likes->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether this video is in the given user's cart.
     */
    public function inCart(?string $userId): bool
    {
        return $this->cart->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user has bought this video.
     */
    public function hasBoughtVideo(?string $userId): bool
    {
        return $this->bought->where('user_id', $userId)->isNotEmpty();
    }
}
