<?php

namespace Aayustho\QueryIndex;

use Illuminate\Support\ServiceProvider;
use Aayustho\QueryIndex\Database\Connection;

class QueryIndexServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('mongodb', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // dd($this->app['db']);
        // dd('boot');
    }
}
