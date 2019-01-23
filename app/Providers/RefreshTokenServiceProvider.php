<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
//    public function boot()
//    {
//        //
//    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Laravel\Passport\Bridge\RefreshTokenRepository::class, \App\Repositories\CustomRefreshTokenRepository::class);
    }
}
