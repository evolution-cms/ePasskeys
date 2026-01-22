<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeletePasskeyController
{
    public function __invoke(Request $request, int $id): RedirectResponse
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

        $userId = evo()->getLoginUserID('mgr');
        $passkeyModel = Config::getPasskeyModel();

        $passkey = $passkeyModel::query()
            ->where('id', $id)
            ->where('context', 'mgr')
            ->where('authenticatable_id', $userId)
            ->first();

        if (!$passkey) {
            return redirect()->back()->with('epasskeys.message', 'Passkey not found.');
        }

        $passkey->delete();

        if (function_exists('evo')) {
            evo()->invokeEvent('OnPasskeyDeleted', [
                'context' => 'mgr',
                'user_id' => $userId,
                'passkey_id' => $passkey->id,
                'credential_id' => Log::maskCredentialId($passkey->credential_id),
                'ip' => $request->ip(),
                'user_agent' => (string)$request->userAgent(),
            ]);
        }

        return redirect()->back()->with('epasskeys.message', 'Passkey deleted.');
    }
}
