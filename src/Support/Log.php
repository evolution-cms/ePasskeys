<?php

namespace EvolutionCMS\ePasskeys\Support;

class Log
{
    public static function warning(string $message): void
    {
        if (function_exists('evo')) {
            evo()->logEvent(0, 2, $message, 'ePasskeys');
        }
    }

    public static function error(string $message): void
    {
        if (function_exists('evo')) {
            evo()->logEvent(0, 3, $message, 'ePasskeys');
        }
    }

    public static function info(string $message): void
    {
        if (function_exists('evo')) {
            evo()->logEvent(0, 1, $message, 'ePasskeys');
        }
    }

    public static function maskCredentialId(string $credentialId): string
    {
        $length = strlen($credentialId);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($credentialId, 0, 4) . '...' . substr($credentialId, -4);
    }
}
