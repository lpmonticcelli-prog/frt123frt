<?php
use Illuminate\Support\Facades\Route;

// ==========================================
// SINCRONIA: Foco estrito em SPA (Zero Trust Routing)
// ==========================================
// O regex de exclusão (?!api|build|storage|vendor) impede categoricamente que requisições 
// a assets estáticos falhados ou APIs caiam neste catch-all.
// Isto força o servidor a devolver um erro 404 real em vez de camuflar com a view HTML.
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|build|storage|vendor).*$');