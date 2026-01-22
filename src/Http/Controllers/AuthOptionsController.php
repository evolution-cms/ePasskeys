<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use EvolutionCMS\ePasskeys\Support\Throttle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthOptionsController
{
    public function __invoke(Request $request)
    {
        if (!Config::isContextEnabled('mgr')) {
            abort(404);
        }

        if (!Config::isAllowedOrigin($request)) {
            Log::warning('Blocked auth options request due to invalid origin.');
            abort(403);
        }

        Throttle::check($request, 'epasskeys.auth.options', 10, 60);

        $rpId = Config::getRelyingPartyId($request);
        if ($rpId === null) {
            Log::error('Invalid RP ID for auth options request.');
            abort(400);
        }

        $action = Config::getAction(
            'generate_passkey_authentication_options',
            GeneratePasskeyAuthenticationOptionsAction::class
        );

        $optionsJson = $action->execute(true);
        Session::put('epasskeys.mgr.auth.options', $optionsJson);

        return response($optionsJson, 200, ['Content-Type' => 'application/json']);
    }
}
