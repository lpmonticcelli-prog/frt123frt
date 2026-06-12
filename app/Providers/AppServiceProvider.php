<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // ZT-DEFENSE: ELOQUENT STRICT MODE (ANTI-DDoS N+1)
        // Aborta a aplicação se o desenvolvedor esquecer o Eager Loading ou tentar salvar
        // atributos não preenchíveis, impedindo o colapso do PostgreSQL em picos de tráfego.
        Model::shouldBeStrict(
            $this->app->environment(['local', 'testing', 'homologation'])
        );
    }
}