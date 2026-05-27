<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Carga;
use App\Models\Role;
use App\Models\Embarcador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\B2bCredentialMail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    // ==========================================
    // ABA 1: VISÃO GERAL (KPIs)
    // ==========================================
    public function getDashboardStats(): JsonResponse
    {
        $roleMotoristaId = Role::where('slug', 'motorista')->value('id');
        $roleEmbarcadorId = Role::where('slug', 'embarcador')->value('id');

        $fretesConcluidos = Carga::whereIn('status', ['entregue', 'finalizada'])->count();
        $fretesAtivos = Carga::whereIn('status', ['publicada', 'aceita', 'em_transito', 'coletada'])->count();

        $volumeTransacionado = Carga::where('status', '!=', 'cancelada')->sum('valor_frete');
        $receitaPlataforma = Carga::where('status', '!=', 'cancelada')->sum('taxa_plataforma');

        return response()->json([
            'fretes_concluidos' => $fretesConcluidos,
            'fretes_ativos' => $fretesAtivos,
            'motoristas_ativos' => User::where('role_id', $roleMotoristaId)->where('status', 'active')->count(),
            'embarcadores_ativos' => User::where('role_id', $roleEmbarcadorId)->where('status', 'active')->count(),
            'volume_transacionado' => $volumeTransacionado,
            'receita_plataforma' => $receitaPlataforma,
        ]);
    }

    // ==========================================
    // ABA 2: AUDITORIA KYC (Fila de Espera)
    // ==========================================
    public function usuariosPendentes(): JsonResponse
    {
        $pendentes = User::with(['role', 'motorista', 'embarcador'])
            ->whereIn('status', ['pending', 'em_analise'])
            ->orderBy('created_at', 'asc')
            ->paginate(50); 

        return response()->json($pendentes);
    }

    public function analisarUsuario(Request $request, User $usuario): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,rejected',
            'motivo' => 'nullable|string|max:1000'
        ]);

        $usuario->status = $request->status;
        $usuario->save();

        $acao = $request->status === 'active' ? 'aprovado' : 'rejeitado';

        Log::info("Auditoria KYC: Usuário ID {$usuario->id} foi {$acao} pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => "Usuário {$usuario->name} foi {$acao} com sucesso."
        ]);
    }

    // ==========================================
    // ABA 3: CRM (Base de Usuários / Blocklist)
    // ==========================================
    public function listarTodosUsuarios(): JsonResponse
    {
        $roleAdminId = Role::where('slug', 'admin')->value('id');

        $usuarios = User::with('role')
            ->where('role_id', '!=', $roleAdminId)
            ->orderBy('created_at', 'desc')
            ->paginate(50); 

        return response()->json($usuarios);
    }

    public function alterarStatus(Request $request, User $usuario): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,banned'
        ]);

        if ($usuario->id === 1 || ($usuario->role && $usuario->role->slug === 'admin' && $request->user()->role->slug !== 'admin')) {
            abort(403, 'Tentativa de violação de hierarquia bloqueada. Você não pode alterar o status de um Administrador Root.');
        }

        DB::transaction(function () use ($usuario, $request) {
            $usuario->status = $request->status;
            $usuario->save();

            if ($request->status === 'banned') {
                // ZT-DEFENSE: Kill Switch Absoluto (Ban Evasion Fix)
                // 1. Destrói chaves de API (M2M)
                $usuario->tokens()->delete(); 
                // 2. Destrói Sessões Físicas no Servidor (Stateful Web)
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            }
        });

        $estado = $request->status === 'banned' ? 'banido' : 'restaurado';
        
        Log::warning("Security Ops: Acesso do Usuário ID {$usuario->id} foi {$estado} pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => "Acesso do usuário {$estado} com sucesso."
        ]);
    }

    // ==========================================
    // ABA 4: FINANCEIRO & OPERAÇÕES (Dashboard)
    // ==========================================
    public function relatorioFretes(): JsonResponse
    {
        $fretes = Carga::with(['embarcador', 'motorista.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        $taxaAtual = Cache::rememberForever('global_settings', function () {
            return DB::table('settings')->pluck('value', 'key')->toArray();
        })['taxa_plataforma'] ?? 5.00;

        $fretes->getCollection()->transform(function ($carga) use ($taxaAtual) {
            $carga->taxa_plataforma = $carga->valor_frete * ($taxaAtual / 100);
            return $carga;
        });

        return response()->json($fretes);
    }

    // ==========================================
    // NOVOS MÓDULOS: MESA DE OPERAÇÕES
    // ==========================================
    public function listarMuralFretes(): JsonResponse
    {
        $cargas = Carga::with(['embarcador', 'motorista.user'])
            ->whereNotIn('status', ['entregue', 'cancelada'])
            ->orderBy('created_at', 'desc')
            ->paginate(50); 
            
        return response()->json($cargas);
    }

    public function listarDisputas(): JsonResponse
    {
        $disputas = Carga::with(['embarcador', 'motorista.user'])
            ->where('status', 'em_disputa')
            ->paginate(50); 
            
        return response()->json($disputas);
    }

    public function resolverDisputa(Request $request, Carga $carga): JsonResponse
    {
        $request->validate([
            'acao' => 'required|in:cancelar,finalizar'
        ]);

        if ($carga->status !== 'em_disputa') {
            return response()->json(['message' => 'Esta carga não está em disputa.'], 400);
        }

        $novoStatus = $request->acao === 'cancelar' ? 'cancelada' : 'finalizada';
        
        DB::transaction(function () use ($carga, $novoStatus) {
            $lockedCarga = Carga::where('id', $carga->id)->lockForUpdate()->firstOrFail();
            $lockedCarga->update(['status' => $novoStatus]);
            
            if ($novoStatus === 'finalizada') {
                $ciot = \App\Models\Ciot::where('carga_id', $lockedCarga->id)->lockForUpdate()->first();
                if ($ciot && $ciot->status === 'bloqueado_disputa') {
                    $ciot->update(['status' => 'processando_liquidacao']);
                    \App\Jobs\LiquidarFreteJob::dispatch($ciot->codigo_ciot)->onQueue('financeiro');
                }
            } else if ($novoStatus === 'cancelada') {
                $ciot = \App\Models\Ciot::where('carga_id', $lockedCarga->id)->first();
                if ($ciot) {
                    $ciot->update(['status' => 'cancelado']);
                }
            }
        });

        Log::info("Disputa Resolvida: Carga ID {$carga->id} alterada para {$novoStatus} pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => "Disputa resolvida com sucesso. Status alterado para: " . strtoupper($novoStatus)
        ]);
    }

    // ==========================================
    // ARQUIVO MORTO E AUDITORIA (TELEMETRIA B2B)
    // ==========================================
    
    public function fretesConcluidos(Request $request): JsonResponse
    {
        $fretes = Carga::with(['embarcador', 'motorista.user'])
            ->whereIn('status', ['entregue', 'pago', 'concluido', 'finalizada', 'em_auditoria'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return response()->json($fretes);
    }

    public function auditoriaCarga(int $id): JsonResponse
    {
        $carga = Carga::with([
            'embarcador.user', 
            'motorista.user', 
            'publicacoesLog.embarcador.user', 
            'aceitesLog'
        ])->findOrFail($id);

        $timeline = collect();

        // 1. Criação
        $timeline->push([
            'evento' => 'Carga Criada',
            'data' => $carga->created_at,
            'descricao' => "Carga registrada por " . ($carga->embarcador->razao_social ?? 'Embarcador'),
            'icone' => 'plus',
            'cor' => 'blue'
        ]);

        // 2. Publicações/Aprovações
        foreach ($carga->publicacoesLog as $log) {
            $responsavel = $log->embarcador->user->name ?? 'Sistema/Embarcador';
            $ip = $log->ip_address ?? 'Desconhecido';
            
            $timeline->push([
                'evento' => 'Carga Publicada',
                'data' => $log->publicado_em ?? $log->created_at,
                'descricao' => "Carga enviada para o mural de fretes por {$responsavel} (IP: {$ip})",
                'icone' => 'bullhorn',
                'cor' => 'indigo'
            ]);
        }

        // 3. Aceite do Motorista
        if ($carga->motorista_id) {
            $primeiroAceite = collect($carga->aceitesLog)->first();
            $dataAceite = $primeiroAceite ? $primeiroAceite->created_at : clone $carga->updated_at;
            
            $timeline->push([
                'evento' => 'Motorista Atribuído',
                'data' => $dataAceite,
                'descricao' => "O motorista " . ($carga->motorista->user->name ?? 'N/A') . " assumiu a carga.",
                'icone' => 'truck',
                'cor' => 'green'
            ]);
        }

        // 4. Início da Viagem
        if ($carga->data_saida) {
            $timeline->push([
                'evento' => 'Início do Transporte',
                'data' => $carga->data_saida,
                'descricao' => "O motorista iniciou o deslocamento para o destino.",
                'icone' => 'play',
                'cor' => 'orange'
            ]);
        }

        // 5. Entrega e POD
        if (in_array($carga->status, ['em_auditoria', 'entregue', 'pago', 'concluido', 'finalizada']) || $carga->foto_canhoto) {
            $timeline->push([
                'evento' => 'Entrega Confirmada',
                'data' => clone $carga->updated_at,
                'descricao' => "O motorista sinalizou a entrega no destino final.",
                'icone' => 'check-circle',
                'cor' => 'emerald',
                'evidencia' => $carga->foto_canhoto ? url("/api/v1/admin/auditoria/documento?path=" . urlencode($carga->foto_canhoto)) : null
            ]);
        }

        // ==========================================
        // EXTRAÇÃO DOS CONTRATOS DIGITAIS (ASSINATURAS)
        // ==========================================
        $publicacaoLog = $carga->publicacoesLog->first();
        $aceiteLog = $carga->aceitesLog ? $carga->aceitesLog->first() : null;

        $contratos = [
            'embarcador' => [
                'valido' => $publicacaoLog ? true : false,
                'nome' => $carga->embarcador->razao_social ?? 'N/A',
                'documento' => $carga->embarcador->cnpj ?? 'N/A',
                'data_assinatura' => $publicacaoLog->publicado_em ?? $carga->created_at,
                'ip_assinatura' => $publicacaoLog->ip_address ?? 'Registado pelo Sistema',
                'hash_certificado' => $publicacaoLog->termo_hash ?? hash('sha256', $carga->id . $carga->created_at . config('app.key') . 'EMB')
            ],
            'motorista' => [
                'valido' => $carga->motorista_id ? true : false,
                'nome' => $carga->motorista->user->name ?? 'N/A',
                'documento' => $carga->motorista->cpf ?? 'N/A',
                'data_assinatura' => $aceiteLog->created_at ?? $carga->updated_at,
                'ip_assinatura' => $aceiteLog->ip_address ?? 'Capturado via App',
                'hash_certificado' => $aceiteLog->termo_hash ?? hash('sha256', $carga->id . $carga->motorista_id . config('app.key') . 'MOT')
            ]
        ];

        return response()->json([
            'carga' => $carga,
            'timeline' => $timeline->sortBy('data')->values(),
            'contratos' => $contratos
        ]);
    }

    /**
     * MOTOR DE SEGURANÇA: Validador de Diretório Estrito (Anti-LFI / Anti-Path Traversal)
     */
    private function resolveAndValidateSecurePath(?string $path, string $allowedPrefix): string
    {
        if (empty($path)) {
            abort(400, 'Path do arquivo é obrigatório.');
        }

        // 1. Desfaz Double Encoding
        $decodedPath = rawurldecode($path);
        
        // 2. Padronização de Barras
        $normalizedPath = str_replace('\\', '/', $decodedPath);

        // 3. Extirpação de Null Bytes
        if (str_contains($normalizedPath, "\0")) {
            abort(403, 'Violação detectada: Null byte injection.');
        }

        // 4. Bloqueio definitivo de escalonamento de diretório
        if (preg_match('#(?:^|/)\.\.(?:/|$)#', $normalizedPath) || str_contains($normalizedPath, '../')) {
            abort(403, 'Violação detectada: Path Traversal (Escalonamento de Diretório).');
        }

        // 5. Bloqueio de consulta à raiz do sistema
        if (str_starts_with($normalizedPath, '/')) {
            abort(403, 'Violação detectada: Consulta de caminho absoluto não autorizada.');
        }

        // 6. Whitelisting (O cofre)
        if (!str_starts_with($normalizedPath, $allowedPrefix . '/')) {
            abort(403, 'Violação detectada: Tentativa de leitura fora do perímetro isolado.');
        }

        return $normalizedPath;
    }

    public function exibirDocumentoAuditoria(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $path = $this->resolveAndValidateSecurePath($request->query('path'), 'pod');

            if (!Storage::disk('local')->exists($path)) {
                return response()->json(['error' => 'Arquivo de auditoria não localizado no cofre seguro.'], 404);
            }

            return Storage::disk('local')->response($path);
        } catch (\Exception $e) {
            Log::alert("Security Audit (LFI/Traversal Blocked) - Modulo: Auditoria | IP: {$request->ip()} | Payload: " . $request->query('path'));
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    public function exibirDocumentoKyc(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $path = $this->resolveAndValidateSecurePath($request->query('path'), 'kyc');

            if (!Storage::disk('local')->exists($path)) {
                return response()->json(['error' => 'Arquivo KYC não localizado no cofre seguro.'], 404);
            }

            return Storage::disk('local')->response($path);
        } catch (\Exception $e) {
            Log::alert("Security Audit (LFI/Traversal Blocked) - Modulo: KYC | IP: {$request->ip()} | Payload: " . $request->query('path'));
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    // ==========================================
    // NOVOS MÓDULOS: CRM ESPECÍFICO
    // ==========================================
    public function listarMotoristas(): JsonResponse
    {
        $roleMotoristaId = Role::where('slug', 'motorista')->value('id');
        
        $motoristas = User::with('motorista')
            ->where('role_id', $roleMotoristaId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return response()->json($motoristas);
    }

    public function listarEmbarcadores(): JsonResponse
    {
        $roleEmbarcadorId = Role::where('slug', 'embarcador')->value('id');
        
        $embarcadores = User::with('embarcador')
            ->where('role_id', $roleEmbarcadorId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return response()->json($embarcadores);
    }

    public function atualizarContratoEmbarcador(Request $request, Embarcador $embarcador): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita. Apenas administradores raiz podem alterar as taxas financeiras de um cliente.');
        }

        $validated = $request->validate([
            'taxa_frete_percentual' => 'nullable|numeric|min:0|max:100',
            'mensalidade_fixa' => 'nullable|numeric|min:0'
        ]);

        $embarcador->update([
            'taxa_frete_percentual' => $validated['taxa_frete_percentual'],
            'mensalidade_fixa' => $validated['mensalidade_fixa']
        ]);

        Log::notice("CRM Financeiro: Contrato do Embarcador ID {$embarcador->id} alterado pelo Admin ID " . auth()->id());

        return response()->json(['message' => 'Contrato financeiro atualizado com sucesso.']);
    }

    // ==========================================
    // NOVOS MÓDULOS: FINANCEIRO E EXTRATOS
    // ==========================================
    public function extratoTaxas(): JsonResponse
    {
        $settings = Cache::get('global_settings') ?? DB::table('settings')->pluck('value', 'key')->toArray();
        $taxaVigente = $settings['taxa_plataforma'] ?? 5.00;

        $extrato = Carga::whereIn('status', ['entregue', 'finalizada'])
            ->select('id', 'valor_frete', 'cidade_origem', 'cidade_destino', 'created_at', 'status', 'taxa_plataforma')
            ->orderBy('created_at', 'desc')
            ->paginate(100);
            
        $extrato->getCollection()->transform(function ($carga) use ($taxaVigente) {
            $carga->taxa_retida = $carga->taxa_plataforma ?? ($carga->valor_frete * ($taxaVigente / 100));
            $carga->percentual_aplicado = $carga->taxa_plataforma 
                ? round(($carga->taxa_plataforma / $carga->valor_frete) * 100, 2) 
                : $taxaVigente;
                
            return $carga;
        });
            
        return response()->json([
            'dados' => $extrato,
            'configuracao_atual' => $taxaVigente
        ]);
    }

    public function relatorioFaturamento(): JsonResponse
    {
        $faturamentoAgregado = Carga::with('embarcador.user:id,name,email')
            ->whereIn('status', ['entregue', 'finalizada'])
            ->selectRaw('embarcador_id, COUNT(id) as total_fretes, SUM(valor_frete) as volume_bruto')
            ->groupBy('embarcador_id')
            ->paginate(50);

        return response()->json($faturamentoAgregado);
    }

    // ==========================================
    // NOVOS MÓDULOS: CONFIGURAÇÕES CORE E STAFF
    // ==========================================
    public function listarStaff(): JsonResponse
    {
        $staffRoles = Role::whereIn('slug', ['admin', 'manager', 'compliance', 'suporte_n1'])->pluck('id');
        
        $staff = User::with('role')
            ->whereIn('role_id', $staffRoles)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($staff);
    }

    public function criarStaff(Request $request): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita. Apenas administradores raiz podem criar membros da equipe.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*[!@#$&*])(?=.*[0-9]).{8,}$/', 
            'role_slug' => 'required|in:admin,manager,compliance,suporte_n1'
        ]);

        $role = Role::where('slug', $request->role_slug)->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'status' => 'active' 
        ]);

        Log::notice("Staff Ops: Novo membro {$user->email} ({$role->slug}) criado pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => 'Membro da equipe adicionado com sucesso. A ação foi registrada em auditoria.',
            'user' => $user->load('role')
        ], 201);
    }

    public function atualizarStaff(Request $request, User $usuario): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita.');
        }

        $request->validate([
            'role_slug' => 'required|in:admin,manager,compliance,suporte_n1',
            'status' => 'required|in:active,suspended,banned'
        ]);

        $role = Role::where('slug', $request->role_slug)->firstOrFail();

        DB::transaction(function () use ($usuario, $role, $request) {
            $usuario->update([
                'role_id' => $role->id,
                'status' => $request->status
            ]);

            if (in_array($request->status, ['banned', 'suspended'], true)) {
                // ZT-DEFENSE: Kill Switch Absoluto (Ban Evasion Fix)
                // 1. Destrói chaves de API (M2M)
                $usuario->tokens()->delete(); 
                // 2. Destrói Sessões Físicas no Servidor (Stateful Web)
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
            }
        });

        Log::notice("Staff Ops: Privilégios/Status do membro ID {$usuario->id} alterados para {$request->status} ({$role->slug}) pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => 'Privilégios e status atualizados com sucesso. Ação registada na auditoria.'
        ]);
    }

    public function listarVariaveis(): JsonResponse
    {
        $settings = Cache::rememberForever('global_settings', function () {
            return DB::table('settings')->pluck('value', 'key')->toArray();
        });

        return response()->json([
            'taxa_plataforma' => $settings['taxa_plataforma'] ?? 5.00,
            'tempo_limite_aceite_minutos' => $settings['tempo_limite_aceite_minutos'] ?? 15,
            'dias_faturamento' => $settings['dias_faturamento'] ?? 15
        ]);
    }

    public function atualizarVariaveis(Request $request): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita.');
        }

        $validated = $request->validate([
            'taxa_plataforma' => 'required|numeric|min:0|max:100',
            'tempo_limite_aceite_minutos' => 'required|integer|min:1',
            'dias_faturamento' => 'required|integer|min:1'
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
        });

        Cache::forget('global_settings');

        Log::warning("SysAdmin: Variáveis globais do sistema alteradas pelo Admin ID " . auth()->id());

        return response()->json(['message' => 'Variáveis operacionais atualizadas com sucesso.']);
    }

    // ==========================================
    // NOVOS MÓDULOS: GESTÃO DE INTEGRAÇÕES (API GATEWAY)
    // ==========================================
    public function listarParceirosApi(): JsonResponse
    {
        $tokens = \Laravel\Sanctum\PersonalAccessToken::with('tokenable')
            ->where('name', 'like', 'API%')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tokens);
    }

    public function gerarTokenParceiro(Request $request): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita. Apenas administradores raiz podem gerar chaves de integração.');
        }

        $validated = $request->validate([
            'nome_parceiro' => 'required|string|max:255',
            'tipo_acesso'   => 'required|in:gr-partner,seguradora-partner,erp-partner,gateway-partner',
            'email_contato' => 'required|email'
        ]);

        // ZT-DEFENSE: Eliminação de Fallback Root (Privilege Escalation Fix)
        // A role "embarcador" DEVE existir. Caso contrário, Fail-Fast (abort 404).
        $roleEmbarcador = Role::where('slug', 'embarcador')->firstOrFail();

        $parceiro = User::firstOrCreate(
            ['email' => $validated['email_contato']],
            [
                'name' => 'B2B - ' . $validated['nome_parceiro'],
                'phone' => '00000000000',
                'password' => Hash::make(Str::random(40)),
                'role_id' => $roleEmbarcador->id,
                'status' => 'active'
            ]
        );

        $tokenName = 'API - ' . $validated['nome_parceiro'];
        
        $token = $parceiro->createToken($tokenName, [$validated['tipo_acesso']])->plainTextToken;

        try {
            Mail::to($validated['email_contato'])->send(
                new B2bCredentialMail($validated['nome_parceiro'], $token, $validated['tipo_acesso'])
            );
            Log::info("API Gateway: E-mail de credenciais enviado para {$validated['email_contato']}");
        } catch (\Exception $e) {
            Log::error("API Gateway: Falha ao enviar e-mail de credencial para {$validated['email_contato']} - Erro: " . $e->getMessage());
        }

        Log::warning("API Gateway: Nova chave gerada para {$validated['nome_parceiro']} ({$validated['tipo_acesso']}) pelo Admin ID " . auth()->id());

        return response()->json([
            'message' => 'Token B2B gerado com sucesso e enviado por e-mail para a corretora.',
            'token' => $token,
            'parceiro' => $parceiro->name,
            'scope' => $validated['tipo_acesso']
        ]);
    }

    public function revogarTokenParceiro(Request $request, string $tokenId): JsonResponse
    {
        if ($request->user()->role->slug !== 'admin') {
            abort(403, 'Ação restrita. Acesso negado ao Kill Switch.');
        }

        $token = \Laravel\Sanctum\PersonalAccessToken::findOrFail($tokenId);
        $tokenName = $token->name;
        $token->delete();

        Log::warning("API Gateway: Kill Switch ativado. Integração {$tokenName} revogada pelo Admin ID " . auth()->id());

        return response()->json(['message' => 'Acesso revogado com sucesso. A porta da API foi fechada para este parceiro.']);
    }

    // ==============================================================
    // ADICIONADOS AGORA: Métodos requeridos pelas rotas originais
    // ==============================================================
    
    public function dashboardMetrics(): JsonResponse
    {
        return $this->getDashboardStats();
    }

    public function detalhesEmbarcador(int $id): JsonResponse
    {
        return response()->json(User::with('embarcador')->findOrFail($id));
    }

    public function detalhesMotorista(int $id): JsonResponse
    {
        return response()->json(User::with('motorista')->findOrFail($id));
    }

    public function avaliarKycMotorista(Request $request, int $id): JsonResponse
    {
        $usuario = User::findOrFail($id);
        return $this->analisarUsuario($request, $usuario);
    }

    public function listarFretes(Request $request): JsonResponse
    {
        $query = Carga::with(['embarcador', 'motorista.user'])->orderBy('created_at', 'desc');

        if ($request->query('status') === 'concluidos') {
            $query->whereIn('status', ['entregue', 'pago', 'concluido', 'finalizada', 'em_auditoria']);
        } else {
            $query->whereNotIn('status', ['entregue', 'cancelada']);
        }

        return response()->json($query->paginate(50));
    }

    public function detalhesFrete(int $id): JsonResponse
    {
        return response()->json(Carga::with(['embarcador', 'motorista.user'])->findOrFail($id));
    }

    public function obterVariaveis(): JsonResponse
    {
        return $this->listarVariaveis();
    }
}