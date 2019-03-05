<?php
/**
 * This file is a part of Laravel Path History package.
 * Email:       mii18@yandex.ru
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory;

use Illuminate\Support\ServiceProvider;
use Malyusha\PathHistory\Http\RouteRegistrar;

class PathHistoryServiceProvider extends ServiceProvider
{
    const MIGRATION_NAME = 'create_path_history';

    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/path_history.php' => config_path('path_history.php'),
        ], 'config');

        if (! $this->migrationExists()) {
            $timestamp = date('Y_m_d_His');
            $name = static::MIGRATION_NAME;
            $this->publishes([
                __DIR__."/../database/migrations/{$name}.php.stub" => database_path("migrations/{$timestamp}_{$name}.php"),
            ], 'migrations');
        }

        $this->registerModelBindings();
        $this->observeModels();

    }

    public function registerModelBindings()
    {
        $this->app->bind(\Malyusha\PathHistory\Contracts\PathHistoryContract::class, config('path_history.model'));
    }

    public function observeModels()
    {
        $model = $this->app->make(\Malyusha\PathHistory\Contracts\PathHistoryContract::class);

        $model::observe(PathHistoryModelObserver::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/path_history.php', 'path_history');
        $this->app->singleton('ph.router', RouteRegistrar::class);
    }

    /**
     * @return bool
     */
    public function migrationExists(): bool
    {
        $files = glob(database_path('migrations/*.php'));
        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (ends_with($file, static::MIGRATION_NAME.'.php')) {
                return true;
            }
        }

        return false;
    }

    public function provides()
    {
        return [
            'ph.router',
        ];
    }
}
