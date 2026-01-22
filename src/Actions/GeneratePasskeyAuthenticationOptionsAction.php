<?php

namespace EvolutionCMS\ePasskeys\Actions;

use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialRequestOptions;

class GeneratePasskeyAuthenticationOptionsAction
{
    public function execute(bool $asJson = true): string|PublicKeyCredentialRequestOptions
    {
        $options = new PublicKeyCredentialRequestOptions(
            challenge: random_bytes(32),
            rpId: Config::getRelyingPartyId(request()),
            allowCredentials: [],
        );

        if ($asJson) {
            return Serializer::make()->toJson($options);
        }

        return $options;
    }
}
