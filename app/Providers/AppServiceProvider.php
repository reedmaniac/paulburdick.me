<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \DB::listen(
            function ($sql, $bindings, $time) {
                \Log::debug('The SQL: ', (array) $sql);
                \Log::debug('The Bindings: ', (array) $sql);
            }
        );
    }
}
