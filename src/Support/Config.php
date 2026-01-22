<?php

namespace EvolutionCMS\ePasskeys\Support;

use Illuminate\Http\Request;

class Config
{
    public static function settings(): array
    {
        $settings = config('cms.settings.ePasskeys', []);
        return is_array($settings) ? $settings : [];
    }

    public static function isEnabled(): bool
    {
        $override = self::getSystemSetting('epasskeys_enable');
        if ($override !== null) {
            return self::toBool($override);
        }

        return self::toBool(self::settings()['enable'] ?? false);
    }

    public static function isContextEnabled(string $context): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $override = self::getSystemSetting('epasskeys_enable_' . $context);
        if ($override !== null) {
            return self::toBool($override);
        }

        $contexts = self::settings()['contexts'] ?? [];
        $contextSettings = $contexts[$context] ?? [];

        return self::toBool($contextSettings['enable'] ?? false);
    }

    public static function getContextRoutePrefix(string $context): string
    {
        $contexts = self::settings()['contexts'] ?? [];
        $contextSettings = $contexts[$context] ?? [];
        $prefix = $contextSettings['route_prefix'] ?? ($context === 'mgr' ? 'webauthn' : 'webauthn');

        return trim($prefix, '/');
    }

    public static function getLoginRedirect(string $context): string
    {
        $contexts = self::settings()['contexts'] ?? [];
        $contextSettings = $contexts[$context] ?? [];
        $redirect = $contextSettings['login_redirect'] ?? '';

        return trim($redirect) !== '' ? $redirect : '';
    }

    public static function getSiteUrl(): string
    {
        if (defined('MODX_SITE_URL')) {
            return rtrim(MODX_SITE_URL, '/') . '/';
        }

        $appUrl = config('app.url', '/');
        return rtrim($appUrl, '/') . '/';
    }

    public static function getManagerUrl(): string
    {
        $candidates = [];
        if (function_exists('config')) {
            $candidates[] = config('cms.manager_url');
            $candidates[] = config('cms.settings.manager_url');
        }

        if (defined('MODX_MANAGER_URL')) {
            $candidates[] = MODX_MANAGER_URL;
        }

        if (function_exists('evo')) {
            $candidates[] = evo()->getConfig('manager_url');
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            return self::normalizeManagerUrl($candidate);
        }

        return rtrim(self::getSiteUrl(), '/') . '/manager/';
    }

    public static function buildManagerUrl(string $path): string
    {
        $base = rtrim(self::getManagerUrl(), '/');
        $path = trim($path);
        if ($path === '') {
            return $base . '/';
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function getRelyingPartyName(): string
    {
        $override = self::getSystemSetting('epasskeys_rp_name');
        if ($override !== null && $override !== '') {
            return (string)$override;
        }

        $name = self::settings()['relying_party']['name'] ?? null;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (function_exists('evo')) {
            $siteName = evo()->getConfig('site_name');
            if (is_string($siteName) && $siteName !== '') {
                return $siteName;
            }
        }

        return (string)config('app.name', 'Evolution CMS');
    }

    public static function getRelyingPartyId(Request $request): ?string
    {
        $override = self::getSystemSetting('epasskeys_rp_id');
        if ($override !== null && $override !== '') {
            return self::normalizeRpId((string)$override);
        }

        $id = self::settings()['relying_party']['id'] ?? null;
        if (is_string($id) && $id !== '') {
            return self::normalizeRpId($id);
        }

        $host = $request->getHost();
        if (!is_string($host) || $host === '') {
            return null;
        }

        if ($host === 'localhost') {
            return 'localhost';
        }

        return self::normalizeRpId($host);
    }

    public static function getRelyingPartyIcon(): ?string
    {
        $icon = self::settings()['relying_party']['icon'] ?? null;
        return is_string($icon) && $icon !== '' ? $icon : null;
    }

    public static function getAllowedOrigins(Request $request): array
    {
        $override = self::getSystemSetting('epasskeys_allowed_origins');
        if ($override !== null) {
            return self::normalizeAllowedOrigins($override);
        }

        $configured = self::settings()['relying_party']['allowed_origins'] ?? [];
        $normalized = self::normalizeAllowedOrigins($configured);
        if ($normalized !== []) {
            return $normalized;
        }

        return self::defaultAllowedOrigins($request);
    }

    public static function isAllowedOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin');
        if (!is_string($origin) || $origin === '') {
            return true;
        }

        $allowed = self::getAllowedOrigins($request);
        if ($allowed === []) {
            return true;
        }

        if (in_array($origin, $allowed, true)) {
            return true;
        }

        if ($request->getHost() === 'localhost') {
            return preg_match('#^https?://localhost(?::\d+)?$#', $origin) === 1;
        }

        return false;
    }

    public static function getOption(string $key, mixed $default = null): mixed
    {
        $options = self::settings()['options'] ?? [];
        return $options[$key] ?? $default;
    }

    public static function getPasskeyModel(): string
    {
        $model = self::settings()['models']['passkey'] ?? null;
        return is_string($model) && $model !== '' ? $model : \\EvolutionCMS\\ePasskeys\\Models\\Passkey::class;
    }

    public static function getAuthenticatableModel(): string
    {
        $model = self::settings()['models']['authenticatable'] ?? null;
        return is_string($model) && $model !== '' ? $model : \EvolutionCMS\Models\User::class;
    }

    public static function getAction(string $actionName, string $defaultClass)
    {
        $actionClass = self::settings()['actions'][$actionName] ?? $defaultClass;
        if (!is_a($actionClass, $defaultClass, true)) {
            throw new \RuntimeException('Invalid action class for ' . $actionName);
        }

        return new $actionClass();
    }

    public static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function normalizeRpId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (strpos($value, ':') !== false && !str_contains($value, ']')) {
            $value = explode(':', $value)[0];
        }

        return $value;
    }

    protected static function normalizeManagerUrl(string $value): string
    {
        if (preg_match('#^https?://#i', $value)) {
            return rtrim($value, '/') . '/';
        }

        if ($value[0] !== '/') {
            $value = '/' . $value;
        }

        return rtrim(self::getSiteUrl(), '/') . rtrim($value, '/') . '/';
    }

    protected static function defaultAllowedOrigins(Request $request): array
    {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $origin = $scheme . '://' . $host;
        if ($port && !in_array($port, [80, 443], true)) {
            $origin .= ':' . $port;
        }

        $allowed = [$origin];

        if ($host === 'localhost') {
            $allowed[] = 'http://localhost';
            $allowed[] = 'https://localhost';
            if ($port) {
                $allowed[] = 'http://localhost:' . $port;
                $allowed[] = 'https://localhost:' . $port;
            }
        }

        return array_values(array_unique($allowed));
    }

    protected static function normalizeAllowedOrigins(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $out[] = $item;
        }

        return array_values(array_unique($out));
    }

    protected static function getSystemSetting(string $key): mixed
    {
        if (!function_exists('evo')) {
            return null;
        }

        $value = evo()->getConfig($key);
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }

    protected static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            if ($value === 'true' || $value === '1') {
                return true;
            }
            if ($value === 'false' || $value === '0') {
                return false;
            }
        }

        return (bool)$value;
    }
}
