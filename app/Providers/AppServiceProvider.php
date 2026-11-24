<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('components.winter-pagination');
        
        View::composer('*', function ($view) {
            static $theme = null;

            if ($theme === null) {
                $theme = Setting::getValue('site_theme', 'christmas');
            }

            $view->with('activeTheme', $theme);
        });
    }
}