<?php

return [
    'enable' => true,
    'contexts' => [
        'mgr' => [
            'enable' => true,
            'route_prefix' => 'webauthn',
            'login_redirect' => '',
        ],
        'web' => [
            'enable' => false,
            'route_prefix' => 'webauthn',
            'login_redirect' => '/',
        ],
    ],
    'relying_party' => [
        'name' => null,
        'id' => null,
        'icon' => null,
        'allowed_origins' => [],
    ],
    'options' => [
        'timeout' => 60000,
        'attestation' => 'none',
        'user_verification' => 'preferred',
        'resident_key' => 'required',
        'authenticator_attachment' => null,
    ],
    'models' => [
        'passkey' => EvolutionCMS\ePasskeys\Models\Passkey::class,
        'authenticatable' => EvolutionCMS\Models\User::class,
    ],
    'actions' => [
        'generate_passkey_register_options' => EvolutionCMS\ePasskeys\Actions\GeneratePasskeyRegisterOptionsAction::class,
        'store_passkey' => EvolutionCMS\ePasskeys\Actions\StorePasskeyAction::class,
        'generate_passkey_authentication_options' => EvolutionCMS\ePasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction::class,
        'find_passkey' => EvolutionCMS\ePasskeys\Actions\FindPasskeyToAuthenticateAction::class,
        'configure_ceremony_step_manager_factory' => EvolutionCMS\ePasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction::class,
    ],
];
