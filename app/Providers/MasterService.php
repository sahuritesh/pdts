<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MasterService extends ServiceProvider
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
        //
    }
    public function insert(){
        return 'Hii';
    }
}
