<?php

namespace EvolutionCMS\ePasskeys;

use EvolutionCMS\ServiceProvider;

class ePasskeysServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadPluginsFrom(dirname(__DIR__) . '/plugins/');
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/ePasskeysCheck.php', 'cms.settings');
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/ePasskeysSettings.php', 'cms.settings.ePasskeys');

        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
        $this->loadTranslationsFrom(dirname(__DIR__) . '/lang', 'ePasskeys');
        $this->loadViewsFrom(dirname(__DIR__) . '/views', 'ePasskeys');

        $this->loadRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }

        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                $this->flattenPublishDirectories();
            }
        });
    }

    protected function loadRoutes(): void
    {
        $this->app->router->middlewareGroup('mgr', config('app.middleware.mgr', []));
        include(__DIR__ . '/Http/routes.php');
    }

    protected function publishResources(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/config/ePasskeysSettings.php' => config_path('cms/settings/ePasskeys.php', true),
        ], 'epasskeys-config');

        $this->publishes([
            dirname(__DIR__) . '/public/js' => public_path('assets/plugins/ePasskeys/js'),
        ], 'epasskeys-assets');

        $this->publishes([
            dirname(__DIR__) . '/views' => base_path('views/vendor/epasskeys'),
        ], 'epasskeys-views');

        $langRoot = $this->resolveLangVendorPath('epasskeys');
        $langSource = dirname(__DIR__) . '/lang';
        if (is_dir($langSource)) {
            $langFiles = $this->collectPublishFiles($langSource, $langRoot);
            if ($langFiles !== []) {
                $this->publishes($langFiles, 'epasskeys-lang');
            }
        }
    }

    protected function flattenPublishDirectories(): void
    {
        if (!class_exists(\Illuminate\Support\ServiceProvider::class)) {
            return;
        }

        $reflection = new \ReflectionClass(\Illuminate\Support\ServiceProvider::class);
        $publishesProperty = $reflection->getProperty('publishes');
        $publishesProperty->setAccessible(true);
        $publishGroupsProperty = $reflection->getProperty('publishGroups');
        $publishGroupsProperty->setAccessible(true);

        $publishes = $publishesProperty->getValue();
        $publishGroups = $publishGroupsProperty->getValue();

        foreach ($publishes as $provider => $paths) {
            $publishes[$provider] = $this->expandPublishPaths($paths);
        }

        foreach ($publishGroups as $group => $paths) {
            $publishGroups[$group] = $this->expandPublishPaths($paths);
        }

        $publishesProperty->setValue(null, $publishes);
        $publishGroupsProperty->setValue(null, $publishGroups);
    }

    protected function expandPublishPaths(array $paths): array
    {
        $expanded = [];

        foreach ($paths as $from => $to) {
            if (is_dir($from)) {
                $files = $this->collectPublishFiles($from, $to);
                if ($files !== []) {
                    $expanded = array_merge($expanded, $files);
                    continue;
                }
            }
            $expanded[$from] = $to;
        }

        return $expanded;
    }

    protected function collectPublishFiles(string $sourceDir, string $targetDir): array
    {
        if (!is_dir($sourceDir)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
        );

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            $relative = substr($path, strlen($sourceDir) + 1);
            $files[$path] = $targetDir . DIRECTORY_SEPARATOR . $relative;
        }

        return $files;
    }

    protected function resolveLangVendorPath(string $package): string
    {
        $base = base_path('lang/vendor');
        if (!is_dir($base)) {
            $base = base_path('resources/lang/vendor');
        }

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $package;
    }
}
