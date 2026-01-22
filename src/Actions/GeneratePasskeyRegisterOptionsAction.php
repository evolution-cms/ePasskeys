<?php

namespace EvolutionCMS\ePasskeys\Actions;

use EvolutionCMS\ePasskeys\Models\Passkey;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Illuminate\Database\Eloquent\Model;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class GeneratePasskeyRegisterOptionsAction
{
    public function execute(Model $authenticatable, string $context, bool $asJson = true): string|PublicKeyCredentialCreationOptions
    {
        $rpId = Config::getRelyingPartyId(request());
        $rp = new PublicKeyCredentialRpEntity(
            name: Config::getRelyingPartyName(),
            id: $rpId,
            icon: Config::getRelyingPartyIcon(),
        );

        $userHandle = Config::base64UrlEncode((string)$authenticatable->getKey());
        $user = new PublicKeyCredentialUserEntity(
            name: $this->userName($authenticatable),
            id: $userHandle,
            displayName: $this->userDisplayName($authenticatable),
        );

        $authenticatorSelection = new AuthenticatorSelectionCriteria(
            Config::getOption('authenticator_attachment'),
            Config::getOption('user_verification', AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED),
            Config::getOption('resident_key', AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED),
        );

        $excludeCredentials = $this->excludeCredentials($authenticatable, $context);

        $options = new PublicKeyCredentialCreationOptions(
            rp: $rp,
            user: $user,
            challenge: random_bytes(32),
            authenticatorSelection: $authenticatorSelection,
            attestation: Config::getOption('attestation', PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE),
            excludeCredentials: $excludeCredentials,
        );

        if ($asJson) {
            return Serializer::make()->toJson($options);
        }

        return $options;
    }

    protected function excludeCredentials(Model $authenticatable, string $context): array
    {
        $passkeyModel = Config::getPasskeyModel();
        $existing = $passkeyModel::query()
            ->where('context', $context)
            ->where('authenticatable_id', $authenticatable->getKey())
            ->get();

        $list = [];
        foreach ($existing as $passkey) {
            if (!$passkey instanceof Passkey) {
                continue;
            }
            $list[] = new PublicKeyCredentialDescriptor(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $passkey->data->publicKeyCredentialId,
            );
        }

        return $list;
    }

    protected function userName(Model $authenticatable): string
    {
        if (method_exists($authenticatable, 'getAttribute')) {
            $username = $authenticatable->getAttribute('username');
            if (is_string($username) && $username !== '') {
                return $username;
            }

            $email = $authenticatable->getAttribute('email');
            if (is_string($email) && $email !== '') {
                return $email;
            }
        }

        return (string)$authenticatable->getKey();
    }

    protected function userDisplayName(Model $authenticatable): string
    {
        if (method_exists($authenticatable, 'attributes')) {
            $attributes = $authenticatable->attributes()->first();
            if ($attributes && property_exists($attributes, 'fullname') && is_string($attributes->fullname)) {
                return $attributes->fullname;
            }
        }

        return $this->userName($authenticatable);
    }
}
