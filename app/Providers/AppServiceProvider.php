<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Dusterio\LumenPassport\LumenPassport;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{


    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        
        LumenPassport::tokensExpireIn(Carbon::now()->addHours(4)); 
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->register(\Tymon\JWTAuth\Providers\LumenServiceProvider::class);
    }
}
