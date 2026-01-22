<?php

namespace EvolutionCMS\ePasskeys\Http\Controllers;

use EvolutionCMS\ePasskeys\Support\Config;
use Illuminate\Http\Request;

class PasskeysController
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

        $userId = evo()->getLoginUserID('mgr');
        $passkeyModel = Config::getPasskeyModel();
        $passkeys = $passkeyModel::query()
            ->where('context', 'mgr')
            ->where('authenticatable_id', $userId)
            ->orderByDesc('id')
            ->get();

        $prefix = Config::getContextRoutePrefix('mgr');
        $managerBase = Config::getManagerUrl();
        $siteBase = rtrim(Config::getSiteUrl(), '/');

        return view('ePasskeys::manager.passkeys', [
            'passkeys' => $passkeys,
            'optionsUrl' => rtrim($managerBase, '/') . '/' . $prefix . '/register/options',
            'registerUrl' => rtrim($managerBase, '/') . '/' . $prefix . '/register',
            'deleteBaseUrl' => rtrim($managerBase, '/') . '/' . $prefix . '/credentials',
            'assetsUrl' => $siteBase . '/assets/plugins/ePasskeys/js',
        ]);
    }
}
