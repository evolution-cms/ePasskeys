<?php

namespace EvolutionCMS\ePasskeys\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class Throttle
{
    public static function check(Request $request, string $key, int $maxAttempts, int $decaySeconds): void
    {
        if (!class_exists(RateLimiter::class)) {
            return;
        }

        $fullKey = $key . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($fullKey, $maxAttempts)) {
            throw new TooManyRequestsHttpException();
        }

        RateLimiter::hit($fullKey, $decaySeconds);
    }
}
