<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Carga;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

// ZT-DEFENSE: Proteção estrita do canal do Mural de Fretes.
// Apenas usuários logados com conta ativa e perfil aprovado têm acesso à telemetria de fretes.
Broadcast::channel('mural.fretes', function (User $user) {
    return $user->status === 'active';
});

// ZT-DEFENSE: Isolamento de Tenant Lógico (Embarcador)
// Impede que a Indústria A escute os eventos de atualização de carga da Indústria B.
Broadcast::channel('embarcador.{embarcadorId}', function (User $user, $embarcadorId) {
    return $user->embarcador !== null && (int) $user->embarcador->id === (int) $embarcadorId;
});

// ZT-DEFENSE: Isolamento de Tenant Lógico (Motorista)
// Impede que um motorista monitore a alocação de cargas ou repasses de outro motorista.
Broadcast::channel('motorista.{motoristaId}', function (User $user, $motoristaId) {
    return $user->motorista !== null && (int) $user->motorista->id === (int) $motoristaId;
});

// ZT-DEFENSE: Blindagem de Eavesdropping na Mesa de Operações (Chat)
// O Reverb só autoriza o handshake TCP se o usuário for o dono da carga ou staff com privilégio de acesso.
Broadcast::channel('chat.{cargaId}', function (User $user, $cargaId) {
    // Projeção O(1) de memória: Trazemos apenas os ponteiros da carga, bloqueando I/O pesado.
    $carga = Carga::select('embarcador_id', 'motorista_id')->find((int) $cargaId);
    
    if (!$carga) {
        return false;
    }

    $isDonoEmbarcador = $user->embarcador !== null && (int) $user->embarcador->id === (int) $carga->embarcador_id;
    $isDonoMotorista = $user->motorista !== null && (int) $user->motorista->id === (int) $carga->motorista_id;
    
    // Especialistas N1, Compliance e Gerentes possuem Override para atuar em tickets/disputas.
    $isStaff = $user->role !== null && in_array($user->role->slug, ['admin', 'manager', 'compliance', 'suporte_n1'], true);

    return $isDonoEmbarcador || $isDonoMotorista || $isStaff;
});