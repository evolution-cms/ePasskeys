<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use EvolutionCMS\ePasskeys\Support\SessionStore;
use EvolutionCMS\ePasskeys\Support\Throttle;
use Illuminate\Http\Request;

class RegisterOptionsController
{
    public function __invoke(Request $request)
    {
        if (!Config::isContextEnabled('mgr')) {
            abort(404);
        }

        if (!function_exists('evo') || !evo()->isLoggedIn('mgr')) {
            abort(403);
        }

        if (method_exists(evo(), 'hasPermission') && !evo()->hasPermission('epasskeys')) {
            abort(403);
        }

        if (!Config::isAllowedOrigin($request)) {
            Log::warning('Blocked register options request due to invalid origin.');
            abort(403);
        }

        Throttle::check($request, 'epasskeys.register.options', 10, 60);

        $rpId = Config::getRelyingPartyId($request);
        if ($rpId === null) {
            Log::error('Invalid RP ID for register options request.');
            abort(400);
        }

        $userId = evo()->getLoginUserID('mgr');
        $modelClass = Config::getAuthenticatableModel();
        $authenticatable = $modelClass::query()->find($userId);
        if (!$authenticatable) {
            abort(404);
        }

        $action = Config::getAction(
            'generate_passkey_register_options',
            GeneratePasskeyRegisterOptionsAction::class
        );

        $optionsJson = $action->execute($authenticatable, 'mgr', true);
        SessionStore::put('epasskeys.mgr.register.options', $optionsJson);

        return response($optionsJson, 200, ['Content-Type' => 'application/json']);
    }
}
