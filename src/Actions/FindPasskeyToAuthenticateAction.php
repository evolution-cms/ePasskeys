<?php

namespace EvolutionCMS\ePasskeys\Actions;

use EvolutionCMS\ePasskeys\Exceptions\InvalidPasskey;
use EvolutionCMS\ePasskeys\Models\Passkey;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class FindPasskeyToAuthenticateAction
{
    public function execute(
        string $context,
        string $publicKeyCredentialJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): ?Passkey {
        $publicKeyCredential = $this->determinePublicKeyCredential($publicKeyCredentialJson);
        if (!$publicKeyCredential) {
            return null;
        }

        $passkey = $this->findPasskey($context, $publicKeyCredential);
        if (!$passkey) {
            return null;
        }

        $passkeyOptions = Serializer::make()->fromJson(
            $passkeyOptionsJson,
            PublicKeyCredentialRequestOptions::class,
        );

        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $publicKeyCredential,
            $passkeyOptions,
            $passkey,
            $hostName,
        );

        if (!$publicKeyCredentialSource) {
            return null;
        }

        $this->updatePasskey($passkey, $publicKeyCredentialSource);

        return $passkey;
    }

    public function determinePublicKeyCredential(string $publicKeyCredentialJson): ?PublicKeyCredential
    {
        if (!json_validate($publicKeyCredentialJson)) {
            throw InvalidPasskey::invalidJson();
        }

        $publicKeyCredential = Serializer::make()->fromJson(
            $publicKeyCredentialJson,
            PublicKeyCredential::class,
        );

        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return null;
        }

        return $publicKeyCredential;
    }

    protected function findPasskey(string $context, PublicKeyCredential $publicKeyCredential): ?Passkey
    {
        $passkeyModel = Config::getPasskeyModel();
        $encodedId = Passkey::encodeCredentialId($publicKeyCredential->rawId);

        return $passkeyModel::query()
            ->where('context', $context)
            ->where('credential_id', $encodedId)
            ->first();
    }

    protected function determinePublicKeyCredentialSource(
        PublicKeyCredential $publicKeyCredential,
        PublicKeyCredentialRequestOptions $passkeyOptions,
        Passkey $passkey,
        string $hostName,
    ): ?PublicKeyCredentialSource {
        $configureCeremonyStepManagerFactoryAction = Config::getAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class
        );
        $csmFactory = $configureCeremonyStepManagerFactoryAction->execute();
        $requestCsm = $csmFactory->requestCeremony();

        try {
            $validator = AuthenticatorAssertionResponseValidator::create($requestCsm);

            return $validator->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $passkeyOptions,
                host: $hostName,
                userHandle: null,
            );
        } catch (Throwable $exception) {
            throw InvalidPasskey::invalidAuthenticatorAssertionResponse($exception);
        }
    }

    protected function updatePasskey(Passkey $passkey, PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $passkey->update([
            'data' => $publicKeyCredentialSource,
            'last_used_at' => now(),
        ]);
    }
}
