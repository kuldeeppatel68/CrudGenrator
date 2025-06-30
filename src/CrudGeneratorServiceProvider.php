<?php

namespace Kuldeep\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes and views
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'crud-generator');

        // Publish for customization (optional)
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/crud-generator'),
            __DIR__ . '/stubs' => base_path('stubs/crud-generator'),
        ], 'crud-generator');
    }

    public function register()
    {
        //
    }
}
