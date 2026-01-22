<?php

namespace EvolutionCMS\ePasskeys\Exceptions;

use RuntimeException;

class InvalidPasskeyOptions extends RuntimeException
{
    public static function invalidJson(): self
    {
        return new self('Invalid passkey options JSON.');
    }
}
