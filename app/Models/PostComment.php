<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'text',
        'media',
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostCommentLike::class);
    }

    /*
     * Custom functions
     */

    /**
     * Whether the given user has liked this comment.
     */
    public function hasLiked(?string $userId): bool
    {
        return $this->likes->where('user_id', $userId)->isNotEmpty();
    }
}
