<?php

namespace KingStarter\LaravelSaml;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Config;

class LaravelSamlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
	public function boot()
	{
        $this->bootInConsole();
        $this->loadPackageRoutes();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/config/saml.php', 'saml'
        );
    }

    /**
     * Perform various commands only if within console
     */
    protected function bootInConsole()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            // Publishing configurations
            $this->publishes([
                __DIR__ . '/config/saml.php' => config_path('saml.php'),
            ], 'saml_config');
            
            // Create storage/saml directory
            if (!file_exists(storage_path() . "/saml/idp")) {
                mkdir(storage_path() . "/saml/idp", 0755, true);
            }
        }
    }

    /**
     * Load package's routes into application
     */
    protected function loadPackageRoutes()
    {
        if (Config::get('saml.use_package_routes')) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }
}
