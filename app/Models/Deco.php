<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deco extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'artist_id',
    ];

    /**
     * users.decos is the number of decos a fan holds (shown next to their
     * name), so keep it in step with the deco rows.
     */
    protected static function booted(): void
    {
        static::created(fn(Deco $deco) => User::query()->whereKey($deco->user_id)->increment('decos'));
        static::deleted(fn(Deco $deco) => User::query()->whereKey($deco->user_id)->decrement('decos'));
    }

    /*
     * Relationships
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
