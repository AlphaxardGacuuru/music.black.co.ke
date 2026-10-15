<?php

namespace App\Http\Middleware;

use App\Http\Services\MPESATransactionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kopokopo\SDK\Helpers\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kopokopo signs every callback and webhook with an HMAC SHA256 of the raw
 * body, keyed with our API key, in the X-KopoKopo-Signature header. Anything
 * unsigned or wrongly signed is rejected, so nobody can forge a "payment
 * received" callback (which would credit a wallet and buy songs) or a
 * "transfer completed" one.
 *
 * For local development only, services.kopokopo.allow_unsigned_callbacks
 * lets hand-made test callbacks through.
 */
class VerifyKopokopoSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (self::allowsUnsigned()) {
            return $next($request);
        }

        if (! self::isValid($request->getContent(), $request->header('X-KopoKopo-Signature'))) {
            Log::warning('Kopokopo callback rejected: missing or invalid signature', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        return $next($request);
    }

    public static function allowsUnsigned(): bool
    {
        return app()->isLocal() && (bool) config('services.kopokopo.allow_unsigned_callbacks');
    }

    public static function isValid(string $payload, ?string $signature): bool
    {
        $apiKey = MPESATransactionService::config()['apiKey'] ?? null;

        if (! $apiKey || ! $signature || $payload === '') {
            return false;
        }

        // The SDK's own check (the same one Webhooks::webhookHandler() uses).
        return (new Auth)->auth($payload, $signature, $apiKey) === 200;
    }
}
