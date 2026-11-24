<?php

namespace EvolutionCMS\ePasskeys\Support;

use EvolutionCMS\Auth\TwoFactor;
use EvolutionCMS\ePasskeys\Actions\FindPasskeyToAuthenticateAction;
use Throwable;

/**
 * Two-factor authentication of Evolution CMS 3.6+, where it exists. Older versions have none, and
 * everything here leaves them as they are.
 */
class SecondFactor
{
    public const METHOD = 'passkey';

    public static function isSupported(): bool
    {
        return class_exists(TwoFactor::class);
    }

    /**
     * Run a passwordless login. A passkey that also verified its user (PIN, fingerprint, face) is two
     * factors at once, so no other one is asked for.
     */
    public static function login(callable $login, bool $userVerified)
    {
        return $userVerified && self::isSupported()
            ? evo()->make(TwoFactor::class)->withoutChallenge($login)
            : $login();
    }

    /** Was the login stopped only until the second factor is given? */
    public static function isPending(Throwable $exception): bool
    {
        return self::isSupported()
            && $exception instanceof \EvolutionCMS\Exceptions\ServiceActionException
            && $exception->getCode() === TwoFactor::PENDING;
    }

    /** Does the manager have to pass their second factor before it may be changed? */
    public static function isLocked(int $userId): bool
    {
        if (!self::isSupported()) {
            return false;
        }

        $user = \EvolutionCMS\Models\User::query()->find($userId);
        $service = evo()->make(TwoFactor::class);

        return $user !== null
            && $service->effective($userId) === self::METHOD
            && $service->isLocked($user, 'mgr');
    }

    /** Only for an assertion that has been validated: the flag is part of what the passkey signs. */
    public static function isUserVerified(FindPasskeyToAuthenticateAction $action, string $response): bool
    {
        try {
            return (bool) $action->determinePublicKeyCredential($response)?->response->authenticatorData->isUserVerified();
        } catch (Throwable) {
            return false;
        }
    }
}
