<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    
    // INJEÇÃO DA ARQUITETURA DE PAGAMENTO E CIOT (123FRETEI)
    App\Providers\PefServiceProvider::class,
];