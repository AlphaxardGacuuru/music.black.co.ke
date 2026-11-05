<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A news story about a local artist that the news job has handled. Its id
 * doubles as the short "/news/{id}" link that redirects to the story.
 */
class NewsArticle extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'title',
        'source',
        'post_id',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Setting the url also stores the hash it's looked up by.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => [
                'url' => $value,
                'url_hash' => static::hashUrl($value),
            ],
        );
    }

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
