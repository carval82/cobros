<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $nombre = config('database.default');
        $configuracion = config('database.connections.'.$nombre);

        if (($configuracion['database'] ?? null) === ':memory:') {
            config(['tenencia.central' => $nombre]);
        } else {
            config(['database.connections.central' => $configuracion]);
            config(['tenencia.central' => 'central']);
        }

        if (config('session.driver') === 'database' && ! config('session.connection')) {
            config(['session.connection' => config('tenencia.central')]);
        }

        if (! config('cache.stores.database.connection')) {
            config(['cache.stores.database.connection' => config('tenencia.central')]);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Carbon::setLocale(config('app.locale'));

        Blade::directive('dinero', function (string $expression) {
            return "<?php echo \\App\\Support\\Dinero::formato({$expression}); ?>";
        });
    }
}
