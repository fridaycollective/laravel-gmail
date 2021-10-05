<?php

namespace FridayCollective\LaravelGmail;

use Illuminate\Support\ServiceProvider;

class LaravelGmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/gmail.php' => config_path('gmail.php'),
        ]);

        switch (config('gmail.load_routes_from')) {
            case 'web':
                $this->loadRoutesFrom(__DIR__.'/routes/web.php');
                break;
            case 'api':
                $this->loadRoutesFrom(__DIR__.'/routes/api.php');
                break;
            default:
                break;
        }

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
