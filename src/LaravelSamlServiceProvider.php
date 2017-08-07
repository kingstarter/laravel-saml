<?php

namespace KingStarter\LaravelSaml;

use Illuminate\Support\ServiceProvider;
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
	    // Publishing configurations
		$this->publishes([
            __DIR__ . '/config/saml.php' => config_path('saml.php'),
		], 'saml_config');

	    // Routing
        if (Config::get('saml.use_package_routes')) {
            require __DIR__ . '/routes.php';
        }
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
}
