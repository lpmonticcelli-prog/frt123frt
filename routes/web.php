<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\GerarFaturasMensaisJob;

// ==========================================
// ROTA TEMPORÁRIA DE LABORATÓRIO (CRON JOB)
// ==========================================
Route::get('/forcar-faturamento', function () {
    // Força a leitura das cargas do mês atual (ex: 05/2026)
    $mesAtual = now()->format('m/Y');
    
    // Roda de forma síncrona (dispatchSync) para podermos ver o resultado na hora
    GerarFaturasMensaisJob::dispatchSync($mesAtual);
    
    return response()->json(['message' => "Robô de faturamento executado com sucesso para o mês {$mesAtual}!"]);
});


// ==========================================
// SINCRONIA: Foco estrito em SPA (Zero Trust Routing)
// ==========================================
// O regex de exclusão (?!api|build|storage|vendor|forcar-faturamento) impede categoricamente que requisições 
// a assets estáticos falhados ou APIs caiam neste catch-all.
// Isto força o servidor a devolver um erro 404 real em vez de camuflar com a view HTML.
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|build|storage|vendor|forcar-faturamento).*$');