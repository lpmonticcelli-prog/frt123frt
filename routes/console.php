<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==========================================
// WORKERS E CRONJOBS DO 123FRETEI
// ==========================================

// Motor de Liquidação Automática (SLA de Pagamento do Motorista)
// Acorda a cada hora cheia (ex: 14:00, 15:00) para evitar retenção indevida de fundos.
Schedule::command('fretei:liquidar-sla')->hourly();