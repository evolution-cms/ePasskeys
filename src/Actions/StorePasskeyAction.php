<?php

namespace EvolutionCMS\ePasskeys\Actions;

use EvolutionCMS\ePasskeys\Exceptions\InvalidPasskey;
use EvolutionCMS\ePasskeys\Exceptions\InvalidPasskeyOptions;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Util\CredentialRecordConverter;

class StorePasskeyAction
{
    public function execute(
        Model $authenticatable,
        string $context,
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
        array $additionalProperties = [],
    ) {
        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName,
        );

        $passkeyModel = Config::getPasskeyModel();

        return $passkeyModel::query()->create([
            'context' => $context,
            'authenticatable_id' => $authenticatable->getKey(),
            ...$additionalProperties,
            'data' => $publicKeyCredentialSource,
        ]);
    }

    protected function determinePublicKeyCredentialSource(
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): PublicKeyCredentialSource {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);
        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskey::invalidPublicKeyCredential();
        }

        $configureCeremonyStepManagerFactory = Config::getAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class
        );
        $csmFactory = $configureCeremonyStepManagerFactory->execute();
        $creationCsm = $csmFactory->creationCeremony();

        try {
            $result = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $passkeyOptions,
                host: $hostName,
            );
            if ($result instanceof CredentialRecord) {
                return CredentialRecordConverter::toPublicKeyCredentialSource($result);
            }

            return $result;
        } catch (Throwable $exception) {
            throw InvalidPasskey::invalidAuthenticatorAttestationResponse($exception);
        }
    }

    protected function getPasskeyOptions(string $passkeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (!json_validate($passkeyOptionsJson)) {
            throw InvalidPasskeyOptions::invalidJson();
        }

        return Serializer::make()->fromJson($passkeyOptionsJson, PublicKeyCredentialCreationOptions::class);
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (!json_validate($passkeyJson)) {
            throw InvalidPasskey::invalidJson();
        }

        return Serializer::make()->fromJson($passkeyJson, PublicKeyCredential::class);
    }
}
