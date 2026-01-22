<?php

namespace EvolutionCMS\ePasskeys\Exceptions;

use RuntimeException;
use Throwable;

class InvalidPasskey extends RuntimeException
{
    public static function invalidJson(): self
    {
        return new self('Invalid passkey JSON.');
    }

    public static function invalidPublicKeyCredential(): self
    {
        return new self('Invalid public key credential response.');
    }

    public static function invalidAuthenticatorAttestationResponse(Throwable $exception): self
    {
        return new self('Invalid authenticator attestation response: ' . $exception->getMessage(), 0, $exception);
    }

    public static function invalidAuthenticatorAssertionResponse(Throwable $exception): self
    {
        return new self('Invalid authenticator assertion response: ' . $exception->getMessage(), 0, $exception);
    }
}
