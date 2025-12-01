<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }   
    public function boot(): void
    {
        if (file_exists(base_path('routes/web.php'))) {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        }

        // Cargar rutas API
        if (file_exists(base_path('routes/api.php'))) {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        }

        // Registrar macro para ignorar verificación SSL SOLO en entornos locales/testing
        if (app()->environment(['local', 'testing'])) {
            Http::macro('noSsl', function () {
                return Http::withoutVerifying();
        });
    }
    }
    
}
