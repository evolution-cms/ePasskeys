<?php

namespace EvolutionCMS\ePasskeys\Support;

class Theme
{
    public static function getManagerThemeMode(): ?string
    {
        $themeModes = ['', 'lightness', 'light', 'dark', 'darkness'];

        if (isset($_COOKIE['MODX_themeMode'])) {
            $index = (int)$_COOKIE['MODX_themeMode'];
            if (!empty($themeModes[$index])) {
                return $themeModes[$index];
            }
        }

        if (function_exists('evo')) {
            $configMode = (int)evo()->getConfig('manager_theme_mode');
            if (!empty($themeModes[$configMode])) {
                return $themeModes[$configMode];
            }
        }

        return null;
    }

    public static function getTailwindThemeClass(): ?string
    {
        $mode = self::getManagerThemeMode();
        if (in_array($mode, ['dark', 'darkness'], true)) {
            return 'darkness';
        }

        return null;
    }
}
