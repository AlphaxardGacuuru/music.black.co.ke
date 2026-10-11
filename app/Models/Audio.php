<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audio extends Model
{
    use HasFactory, HasUuids;

    protected $table = "audios";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audio',
        'name',
        'user_id',
        'ft_id',
        'audio_album_id',
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
    protected function audio(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "storage/" . $value,
        );
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "/storage/" . $value,
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
        return $this->belongsTo(AudioAlbum::class, 'audio_album_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(AudioLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AudioComment::class);
    }

    public function bought(): HasMany
    {
        return $this->hasMany(BoughtAudio::class);
    }

    public function cart(): HasMany
    {
        return $this->hasMany(CartAudio::class);
    }

    public function karaokes(): HasMany
    {
        return $this->hasMany(Karaoke::class);
    }

    /*
     * Custom functions
     */

    /**
     * Whether the given user has liked this audio.
     */
    public function hasLiked(?string $userId): bool
    {
        return $this->likes->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether this audio is in the given user's cart.
     */
    public function inCart(?string $userId): bool
    {
        return $this->cart->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user has bought this audio.
     */
    public function hasBoughtAudio(?string $userId): bool
    {
        return $this->bought->where('user_id', $userId)->isNotEmpty();
    }
}
