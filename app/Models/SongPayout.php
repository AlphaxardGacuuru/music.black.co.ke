<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SongPayout extends Model
{
    use HasFactory, HasUuids;

    /**
     * The artist's cut of every song sold.
     */
    public const ARTIST_SHARE = 0.5;

    /**
     * Withdrawals below this amount carry the provider's charge.
     */
    public const SMALL_PAYOUT_LIMIT = 1000;

    /**
     * The provider's charge on a small withdrawal, in KES.
     */
    public const SMALL_PAYOUT_FEE = 50;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'fee',
        'kopokopo_reference',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fee' => 'integer',
        'paid_at' => 'datetime',
    ];

    /*
     * Relationships
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Custom functions
     */

    /**
     * Everything an artist has earned from sales of their songs.
     */
    public static function earningsFor(User $artist): int
    {
        $sales = BoughtVideo::query()->where('artist_id', $artist->id)->sum('price')
            + BoughtAudio::query()->where('artist_id', $artist->id)->sum('price');

        return (int) floor($sales * self::ARTIST_SHARE);
    }

    /**
     * What the artist can still withdraw: their earnings less every payout
     * that went out or is still on its way (failed payouts are deleted).
     */
    public static function balanceFor(User $artist): int
    {
        return self::earningsFor($artist)
            - (int) static::query()->where('user_id', $artist->id)->sum('amount');
    }

    /**
     * The charge kept back from a withdrawal of the given amount.
     */
    public static function feeFor(int $amount): int
    {
        return $amount < self::SMALL_PAYOUT_LIMIT ? self::SMALL_PAYOUT_FEE : 0;
    }
}
