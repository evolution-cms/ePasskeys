<?php

namespace EvolutionCMS\ePasskeys\Support;

use EvolutionCMS\Auth\TwoFactorMethod;
use EvolutionCMS\ePasskeys\Actions\FindPasskeyToAuthenticateAction;
use EvolutionCMS\ePasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use EvolutionCMS\ePasskeys\Actions\StorePasskeyAction;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Models\User;
use Throwable;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * A passkey as the second sign-in step.
 *
 * Uses the same passkeys as the passwordless login. The class extends one of Evolution CMS 3.6+ and is
 * loaded only there, on the OnTwoFactorMethods event.
 */
class PasskeyTwoFactorMethod extends TwoFactorMethod
{
    public function title(): string
    {
        return __('ePasskeys::global.two_factor_title');
    }

    public function needsSetup(User $user, array $state, string $context): bool
    {
        return !$this->passkeys($user, $context)->exists();
    }

    public function beginSetup(User $user, string $context): array
    {
        $this->host($context);
        $options = json_decode(
            Config::getAction('generate_passkey_register_options', GeneratePasskeyRegisterOptionsAction::class)
                ->execute($user, $context),
            true
        );
        // the challenge requires it, so the device has to be able to
        $options['authenticatorSelection']['userVerification'] = 'required';

        return ['options' => json_encode($options)];
    }

    public function setupForm(User $user, array $setup, string $context): string
    {
        return $this->ceremony('register', $setup['options'], __('ePasskeys::global.two_factor_setup'));
    }

    public function finishSetup(User $user, string $response, array $setup, string $context): ?array
    {
        try {
            $passkey = Config::getAction('store_passkey', StorePasskeyAction::class)->execute(
                $user,
                $context,
                $response,
                $setup['options'],
                $this->host($context),
                ['name' => __('ePasskeys::global.two_factor_device', ['date' => date('Y-m-d')])],
            );
        } catch (Throwable $exception) {
            Log::warning('Passkey was not registered as a second factor: ' . e($exception->getMessage()));

            return null;
        }
        $this->fire('OnPasskeyRegistered', $passkey, $context);

        return [];
    }

    public function begin(User $user, array $state, string $context): array
    {
        $descriptors = [];
        foreach ($this->passkeys($user, $context)->get() as $passkey) {
            $descriptors[] = new PublicKeyCredentialDescriptor(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $passkey->data->publicKeyCredentialId,
            );
        }
        $options = new PublicKeyCredentialRequestOptions(
            challenge: random_bytes(32),
            rpId: $this->host($context),
            allowCredentials: $descriptors,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );

        return ['options' => Serializer::make()->toJson($options)];
    }

    public function form(User $user, array $state, string $context): string
    {
        return $this->ceremony('login', $state['options'] ?? '{}', __('ePasskeys::global.two_factor_hint'));
    }

    public function verify(User $user, string $response, array $state, string $context): ?array
    {
        if (empty($state['options'])) {
            return null;
        }

        try {
            $passkey = Config::getAction('find_passkey', FindPasskeyToAuthenticateAction::class)->execute(
                $context,
                $response,
                $state['options'],
                $this->host($context),
            );
        } catch (Throwable $exception) {
            Log::warning('Passkey was refused as a second factor: ' . e($exception->getMessage()));

            return null;
        }
        if (!$passkey || (int) $passkey->authenticatable_id !== (int) $user->getKey()) {
            return null;
        }
        $this->fire('OnPasskeyAuthenticated', $passkey, $context);

        // the challenge is spent
        return [];
    }

    public function forget(User $user, array $state): void
    {
        Config::getPasskeyModel()::query()->where('authenticatable_id', $user->getKey())->delete();
    }

    protected function passkeys(User $user, string $context)
    {
        return Config::getPasskeyModel()::query()
            ->where('context', $context)
            ->where('authenticatable_id', $user->getKey());
    }

    /**
     * @throws ServiceActionException
     */
    protected function host(string $context): string
    {
        $host = Config::isContextEnabled($context) ? Config::getRelyingPartyId(request()) : null;
        if ($host === null) {
            throw new ServiceActionException(__('ePasskeys::global.two_factor_unavailable'));
        }

        return $host;
    }

    /** The answer goes into the form around it as two_factor_code. */
    protected function ceremony(string $type, string $options, string $text): string
    {
        return view('ePasskeys::two-factor', [
            'type' => $type,
            'options' => json_decode($options),
            'text' => $text,
            'assetsUrl' => rtrim(Config::getSiteUrl(), '/') . '/assets/plugins/ePasskeys/js',
        ])->render();
    }

    protected function fire(string $event, $passkey, string $context): void
    {
        evo()->invokeEvent($event, [
            'context' => $context,
            'user_id' => $passkey->authenticatable_id,
            'passkey_id' => $passkey->id,
            'credential_id' => Log::maskCredentialId($passkey->credential_id),
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
