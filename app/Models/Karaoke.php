<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karaoke extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karaoke',
        'user_id',
        'audio_id',
        'description',
    ];

    /**
     * Accessors.
     */
    protected function karaoke(): Attribute
    {
        return Attribute::make(
            get: fn($value) => "/storage/" . $value,
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

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function savedKaraokes(): HasMany
    {
        return $this->hasMany(SavedKaraoke::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(KaraokeLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(KaraokeComment::class);
    }

    /*
     * Custom functions
     */

    /**
     * Whether the given user has liked this karaoke.
     */
    public function hasLiked(?string $userId): bool
    {
        return $this->likes->where('user_id', $userId)->isNotEmpty();
    }

    /**
     * Whether the given user has saved this karaoke.
     */
    public function hasSaved(?string $userId): bool
    {
        return $this->savedKaraokes->where('user_id', $userId)->isNotEmpty();
    }
}
