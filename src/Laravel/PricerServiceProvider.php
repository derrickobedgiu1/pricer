<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Laravel;

use DerrickOb\Pricer\Laravel\Blade\PricerDirectives;
use DerrickOb\Pricer\Laravel\Commands\PricerInstallCommand;
use DerrickOb\Pricer\Pricer;
use Illuminate\Support\ServiceProvider;

class PricerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/pricer.php',
            'pricer'
        );

        $this->app->singleton(Pricer::class, function (array $app): Pricer {
            $config = $app['config']['pricer'];
            Pricer::configure($config);

            return new Pricer();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/pricer.php' => config_path('pricer.php'),
            ], 'pricer-config');

            $this->commands([
                PricerInstallCommand::class,
            ]);
        }

        PricerDirectives::register();
    }
}
