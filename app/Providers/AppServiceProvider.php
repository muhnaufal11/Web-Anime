<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\AdminEpisodeLog;
use App\Observers\AdminEpisodeLogObserver;

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
        // Register Observer for AdminEpisodeLog (real-time Discord updates)
        AdminEpisodeLog::observe(AdminEpisodeLogObserver::class);

        // 1. INI LOGIKA BARU: Paksa HTTPS jika di Production
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // 2. INI LOGIKA LAMA KAMU (Holiday Settings) - Jangan dihapus
        view()->composer('*', function ($view) {
            $view->with('holidaySettings', [
                'christmas' => \App\Models\SiteSetting::where('key', 'christmas_mode')->first()?->value == '1',
                'new_year' => \App\Models\SiteSetting::where('key', 'new_year_mode')->first()?->value == '1',
            ]);
        });
    }
}
