<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
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
        Carbon::macro('dueDiff', function () {
            /** @var Carbon $this */

            if ($this->diff(now())->days < 1) {
                return '🚨 today';
            }

            if ($this->week === now()->week) {
                return '⚠️ ' . $this->diffForHumans();
            }

            return $this->diffForHumans();
        });
    }
}
