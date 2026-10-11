<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoughtAudio extends Model
{
    use HasFactory, HasUuids;

    /**
     * What every audio costs, in KES.
     */
    public const PRICE = 10;

    protected $table = "bought_audios";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audio_id',
        'user_id',
        'artist_id',
        'price',
    ];

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

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }
}
