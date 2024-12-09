<?php

namespace JayLordIbe\LaravelResourceGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use JayLordIbe\LaravelResourceGenerator\Commands\LaravelResourceGeneratorCommand;

class LaravelResourceGeneratorServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LaravelResourceGeneratorCommand::class
            ]);
        }
    }

}
