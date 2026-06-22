<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Contracts\RiskManagementInterface;
use App\Services\Partners\TransSatService;
use App\Services\Partners\BypassRiskManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ZT-DEFENSE: Padrão Strategy para Gerenciamento de Risco (GR) / TransSat
        // O container entrega o motor pesado ou o leve dependendo da variável de ambiente
        $this->app->bind(RiskManagementInterface::class, function ($app) {
            
            if (config('services.gr.enabled', false)) {
                // Motor Real (TransSat)
                return new TransSatService(); 
            }

            // Motor Leve/Bypass (Sem I/O de rede, ideal para a DigitalOcean)
            return new BypassRiskManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Força HTTPS em produção na DigitalOcean (Evita erros de Mixed Content)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}