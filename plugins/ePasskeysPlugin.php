<?php

use EvolutionCMS\ePasskeys\Support\Config;
use EvolutionCMS\ePasskeys\Support\Log;
use Illuminate\Support\Facades\Schema;

if (!function_exists('ePasskeys_settings')) {
    function ePasskeys_settings(): array
    {
        $settings = config('cms.settings.ePasskeys', []);
        return is_array($settings) ? $settings : [];
    }
}

if (!function_exists('ePasskeys_enabled')) {
    function ePasskeys_enabled(string $context = 'mgr'): bool
    {
        return Config::isContextEnabled($context);
    }
}

Event::listen('evolution.OnManagerLoginFormRender', function () {
    if (!ePasskeys_enabled('mgr')) {
        return '';
    }

    $assetsPath = MODX_BASE_PATH . 'assets/plugins/ePasskeys/js/epasskeys.js';
    $simplePath = MODX_BASE_PATH . 'assets/plugins/ePasskeys/js/simplewebauthn.umd.js';
    if (!is_file($assetsPath) || !is_file($simplePath)) {
        Log::warning('ePasskeys assets are not published. Run vendor:publish for ePasskeys.');
        return '';
    }

    $baseUrl = rtrim(Config::getSiteUrl(), '/') . '/assets/plugins/ePasskeys/js';
    $prefix = Config::getContextRoutePrefix('mgr');
    $optionsUrl = Config::buildManagerUrl($prefix . '/auth/options');
    $authUrl = Config::buildManagerUrl($prefix . '/auth');
    $modelClass = Config::getPasskeyModel();
    $hasPasskey = false;

    try {
        if (!class_exists($modelClass)) {
            $hasPasskey = false;
        } else {
            $model = new $modelClass();
            $table = method_exists($model, 'getTable') ? $model->getTable() : 'passkeys';
            if (class_exists(Schema::class) && Schema::hasTable($table)) {
                $userId = null;
                if (function_exists('evo') && method_exists(evo(), 'getLoginUserID')) {
                    $userId = evo()->getLoginUserID('mgr');
                }
                if (!$userId && isset($_SESSION['mgrInternalKey'])) {
                    $userId = (int)$_SESSION['mgrInternalKey'];
                }
                if (!$userId && isset($_SESSION['mgrShortname'])) {
                    $username = is_string($_SESSION['mgrShortname']) ? $_SESSION['mgrShortname'] : null;
                    if ($username) {
                        $userModel = Config::getAuthenticatableModel();
                        if (class_exists($userModel)) {
                            $userId = $userModel::query()
                                ->where('username', $username)
                                ->value('id');
                        }
                    }
                }
                if (!$userId && isset($_SESSION['mgrEmail'])) {
                    $email = is_string($_SESSION['mgrEmail']) ? $_SESSION['mgrEmail'] : null;
                    if ($email) {
                        $userModel = Config::getAuthenticatableModel();
                        if (class_exists($userModel)) {
                            $userId = $userModel::query()
                                ->where('email', $email)
                                ->value('id');
                        }
                    }
                }
                if ($userId) {
                    $hasPasskey = $modelClass::query()
                        ->where('context', 'mgr')
                        ->where('authenticatable_id', $userId)
                        ->exists();
                } else {
                    // Fallback: if we cannot resolve user, show passkey if any exist.
                    $hasPasskey = $modelClass::query()
                        ->where('context', 'mgr')
                        ->exists();
                }
            }
        }
    } catch (\Throwable $e) {
        Log::warning('ePasskeys passkey lookup failed: ' . $e->getMessage());
    }

    if (!$hasPasskey) {
        return '';
    }

    return \View::make('ePasskeys::manager.login-button', [
        'assetsUrl' => $baseUrl,
        'optionsUrl' => $optionsUrl,
        'authUrl' => $authUrl,
        'hasPasskey' => $hasPasskey,
    ])->toHtml();
});

Event::listen('evolution.OnManagerMenuPrerender', function ($params) {
    if (!ePasskeys_enabled('mgr')) {
        return serialize($params['menu']);
    }

    if (function_exists('evo') && method_exists(evo(), 'hasPermission')) {
        if (!evo()->hasPermission('epasskeys')) {
            return serialize($params['menu']);
        }
    }

    $iconHtml = function_exists('svg') ? svg('tabler-key')->toHtml() : '<i class="tabler-key"></i>';

    $menu = [
        'epasskeys' => [
            'epasskeys',
            'tools',
            $iconHtml . __('ePasskeys::global.menu_title'),
            Config::buildManagerUrl(Config::getContextRoutePrefix('mgr') . '/credentials'),
            __('ePasskeys::global.menu_title'),
            '',
            '',
            'main',
            0,
            7,
        ],
    ];

    return serialize(array_merge($params['menu'], $menu));
});
