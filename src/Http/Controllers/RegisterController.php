<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Actions\StorePasskeyAction;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class RegisterController
{
    public function __invoke(Request $request): RedirectResponse
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
            Log::warning('Blocked register request due to invalid origin.');
            abort(403);
        }

        $request->validate([
            'passkey' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $optionsJson = Session::pull('epasskeys.mgr.register.options');
        if (!is_string($optionsJson) || $optionsJson === '') {
            return redirect()->back()->with('epasskeys.message', 'Registration options expired.');
        }

        $userId = evo()->getLoginUserID('mgr');
        $modelClass = Config::getAuthenticatableModel();
        $authenticatable = $modelClass::query()->find($userId);
        if (!$authenticatable) {
            abort(404);
        }

        $hostName = Config::getRelyingPartyId($request);
        if ($hostName === null) {
            Log::error('Invalid RP ID for register request.');
            return redirect()->back()->with('epasskeys.message', 'Invalid RP ID.');
        }

        $action = Config::getAction(
            'store_passkey',
            StorePasskeyAction::class
        );

        try {
            $passkey = $action->execute(
                $authenticatable,
                'mgr',
                $request->input('passkey'),
                $optionsJson,
                $hostName,
                [
                    'name' => $request->input('name'),
                ],
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to store passkey: ' . $exception->getMessage());
            return redirect()->back()->with('epasskeys.message', 'Failed to store passkey.');
        }

        $this->firePasskeyEvent('OnPasskeyRegistered', $passkey, $request);

        return redirect()->back()->with('epasskeys.message', 'Passkey created.');
    }

    protected function firePasskeyEvent(string $eventName, $passkey, Request $request): void
    {
        if (!function_exists('evo')) {
            return;
        }

        $payload = [
            'context' => 'mgr',
            'user_id' => $passkey->authenticatable_id,
            'passkey_id' => $passkey->id,
            'credential_id' => Log::maskCredentialId($passkey->credential_id),
            'ip' => $request->ip(),
            'user_agent' => (string)$request->userAgent(),
        ];

        evo()->invokeEvent($eventName, $payload);
    }
}
