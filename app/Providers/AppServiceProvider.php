<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            static $theme = null;

            if ($theme === null) {
                $theme = Setting::getValue('site_theme', 'christmas');
            }

            $view->with('activeTheme', $theme);
        });
    }
}