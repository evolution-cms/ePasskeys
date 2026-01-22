<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Actions\FindPasskeyToAuthenticateAction;
use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use EvolutionCMS\ePasskeys\Support\Throttle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class AuthenticateController
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (!Config::isContextEnabled('mgr')) {
            abort(404);
        }

        if (!Config::isAllowedOrigin($request)) {
            Log::warning('Blocked authenticate request due to invalid origin.');
            abort(403);
        }

        Throttle::check($request, 'epasskeys.auth', 5, 60);

        Validator::make($request->all(), [
            'start_authentication_response' => ['required', 'string'],
        ])->validate();

        $optionsJson = Session::pull('epasskeys.mgr.auth.options');
        if (!is_string($optionsJson) || $optionsJson === '') {
            return $this->invalidPasskeyResponse();
        }

        $hostName = Config::getRelyingPartyId($request);
        if ($hostName === null) {
            Log::error('Invalid RP ID for authenticate request.');
            return $this->invalidPasskeyResponse();
        }

        $findAuthenticatableUsingPasskey = Config::getAction(
            'find_passkey',
            FindPasskeyToAuthenticateAction::class
        );

        $passkey = $findAuthenticatableUsingPasskey->execute(
            'mgr',
            $request->input('start_authentication_response'),
            $optionsJson,
            $hostName,
        );

        if (!$passkey) {
            return $this->invalidPasskeyResponse();
        }

        $remember = $request->boolean('remember');
        try {
            \UserManager::loginById([
                'id' => $passkey->authenticatable_id,
                'rememberme' => $remember,
                'context' => 'mgr',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to login via passkey: ' . $e->getMessage());
            return $this->invalidPasskeyResponse();
        }

        $this->firePasskeyEvent('OnPasskeyAuthenticated', $passkey, $request);

        return redirect($this->managerUrl(Config::getLoginRedirect('mgr')));
    }

    protected function invalidPasskeyResponse(): RedirectResponse
    {
        Session::flash('epasskeys.message', 'Invalid passkey.');
        return redirect()->back();
    }

    protected function managerUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return Config::getManagerUrl();
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if ($path[0] === '/') {
            return $path;
        }

        return Config::buildManagerUrl($path);
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
