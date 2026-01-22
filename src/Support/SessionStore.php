<?php

namespace EvolutionCMS\ePasskeys\Support;

use Illuminate\Support\Facades\Session;

class SessionStore
{
    public static function put(string $key, mixed $value): void
    {
        Session::put($key, $value);
        if (isset($_SESSION) && is_array($_SESSION)) {
            $_SESSION[$key] = $value;
        }
    }

    public static function pull(string $key): mixed
    {
        $value = Session::pull($key);
        if ((is_null($value) || $value === '') && isset($_SESSION) && is_array($_SESSION)) {
            if (array_key_exists($key, $_SESSION)) {
                $value = $_SESSION[$key];
                unset($_SESSION[$key]);
            }
        }

        return $value;
    }
}
