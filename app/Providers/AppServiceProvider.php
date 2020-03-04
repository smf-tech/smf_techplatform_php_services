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
        
        LumenPassport::tokensExpireIn(Carbon::now()->addYears(1)); 
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->register(\Tymon\JWTAuth\Providers\LumenServiceProvider::class);

        $this->app->singleton('mailer', function ($app) { 
          $app->configure('services'); 
          return $app->loadComponent('mail', 'Illuminate\Mail\MailServiceProvider', 'mailer'); 
        });
    }
}
