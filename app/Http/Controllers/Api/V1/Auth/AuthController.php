<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use App\Services\ReceitaWSService;
use App\Services\CpfValidatorService;
use App\Services\Security\BlindIndexService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    /**
     * Stateless Authentication: Emissão de Tokens Sanctum com Abilities Estritas.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string']
        ]);

        // Defesa contra Timing Attacks: Ocultação da razão exata da falha (Usuário vs Senha)
        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('[WAF] Tentativa de login falha.', ['ip' => $request->ip(), 'email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas são inválidas.']
            ]);
        }

        // KYC & Compliance Gate
        if ($user->status === 'banned') {
            Log::alert('[WAF] Tentativa de acesso por conta banida.', ['user_id' => $user->id, 'ip' => $request->ip()]);
            throw ValidationException::withMessages([
                'email' => ['Acesso revogado. Esta conta foi banida permanentemente por violação dos termos de segurança.']
            ]);
        }

        // ZT-DEFENSE: Emissão de Token Epêmero com Escopo Restrito (Abilities)
        \Illuminate\Support\Facades\Auth::login($user, true);
        $ability = $user->role ? "ability:{$user->role->slug}" : 'ability:none';
        $deviceName = $request->userAgent() ?? 'unknown_device';

        // Expira tokens antigos do mesmo dispositivo para evitar acúmulo de estado no banco
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName, [$ability], now()->addHours(12))->plainTextToken;

        Log::info('[IAM] Autenticação Stateless realizada com sucesso.', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Autenticado com sucesso.',
            'token'   => $token,
            'user'    => $user
        ], 200);
    }

    public function registerEmbarcador(Request $request, ReceitaWSService $receitaWSService): JsonResponse
    {
        $this->validateEmbarcadorRegistration($request);

        $analiseCNPJ = $receitaWSService->validarCNPJ($request->cnpj);
            
        if (!$analiseCNPJ['valido']) {
            return response()->json([
                'error'   => 'Validação fiscal rejeitada pela Receita Federal.',
                'details' => $analiseCNPJ['mensagem']
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $analiseCNPJ) {
                $role = Role::where('slug', 'embarcador')->firstOrFail();

                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'phone'    => $request->phone,
                    'role_id'  => $role->id,
                    'status'   => 'pending' // Aguardando onboarding/contrato
                ]);

                Embarcador::create([
                    'user_id'            => $user->id,
                    'razao_social'       => $analiseCNPJ['razao_social'] ?? $request->razao_social,
                    'cnpj'               => $request->cnpj,
                    'inscricao_estadual' => $request->inscricao_estadual,
                ]);

                $token = $user->createToken('registro_inicial', ["ability:embarcador"], now()->addHours(12))->plainTextToken;

                Log::info('[IAM] Novo Embarcador registrado.', ['user_id' => $user->id, 'cnpj' => $request->cnpj]);

                return response()->json([
                    'message' => 'Conta criada com sucesso. Bem-vindo à plataforma.',
                    'token'   => $token,
                    'user'    => $user->load('role')
                ], 201);
            });
        } catch (Throwable $e) {
            Log::critical('[IAM] Falha transacional ao registrar Embarcador.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao processar o registro.'], 500);
        }
    }

    public function registerMotorista(Request $request, CpfValidatorService $cpfValidator): JsonResponse
    {
        $this->validateMotoristaRegistration($request);

        if (!$cpfValidator->isValid($request->cpf)) {
            return response()->json([
                'error'   => 'Documento rejeitado pela malha de segurança.',
                'details' => 'O CPF informado falhou na verificação de checksum (Dígito Verificador).'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $role = Role::where('slug', 'motorista')->firstOrFail();

                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'phone'    => $request->phone,
                    'role_id'  => $role->id,
                    'status'   => 'pending' // Isolado até aprovação do KYC
                ]);

                Motorista::create([
                    'user_id'       => $user->id,
                    'cpf'           => $request->cpf,
                    'cnh'           => $request->cnh,
                    'validade_cnh'  => $request->validade_cnh,
                    'rntrc'         => $request->rntrc,
                    'is_disponivel' => false,
                ]);

                $token = $user->createToken('registro_inicial', ["ability:motorista"], now()->addHours(12))->plainTextToken;

                Log::info('[IAM] Novo Motorista submetido para KYC.', ['user_id' => $user->id, 'cpf' => $request->cpf]);

                return response()->json([
                    'message' => 'Conta submetida com sucesso. Aguardando liberação compliance (KYC).',
                    'token'   => $token,
                    'user'    => $user->load('role')
                ], 201);
            });
        } catch (Throwable $e) {
            Log::critical('[IAM] Falha transacional ao registrar Motorista.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha interna ao processar o registro.'], 500);
        }
    }

    public function me(Request $request): JsonResponse 
    { 
        return response()->json($request->user()->load('role')); 
    }
    
    /**
     * Stateless Logout: Revoga o token atual do dispositivo.
     */
    public function logout(Request $request): JsonResponse 
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sessão revogada de forma segura.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        
        // Operação assíncrona blindada: Não confirma a existência do e-mail para evitar enumeração.
        Password::sendResetLink($request->only('email'));
        
        return response()->json([
            'message' => 'Se o e-mail constar em nossa base ativa, um link de recuperação será enviado.'
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Força o logout global do usuário em todos os dispositivos por segurança
                $user->tokens()->delete();

                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('[IAM] Redefinição de senha executada.', ['email' => $request->email]);
            return response()->json(['message' => 'A sua senha foi redefinida com sucesso.']);
        }

        throw ValidationException::withMessages([
            'email' => ['O link de recuperação é inválido ou expirou. Solicite um novo.']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Private Validation Gates (Isolamento Ciclomático)
    |--------------------------------------------------------------------------
    */

    private function validateEmbarcadorRegistration(Request $request): void
    {
        $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'phone'              => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (User::where('phone_bidx', $bidx)->exists()) {
                        $fail('Este telefone já encontra-se vinculado a outro cadastro.');
                    }
                }
            ],
            'razao_social'       => ['required', 'string', 'max:150'],
            'cnpj'               => [
                'required', 'string', 'max:18',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (Embarcador::where('cnpj_bidx', $bidx)->exists()) {
                        $fail('Este CNPJ já está registrado na plataforma.');
                    }
                }
            ], 
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function validateMotoristaRegistration(Request $request): void
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'phone'        => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (User::where('phone_bidx', $bidx)->exists()) {
                        $fail('Telefone já cadastrado no sistema.');
                    }
                }
            ],
            'cpf'          => [
                'required', 'string', 'max:14',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (Motorista::where('cpf_bidx', $bidx)->exists()) {
                        $fail('Este CPF já está cadastrado.');
                    }
                }
            ],
            'cnh'          => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (Motorista::where('cnh_bidx', $bidx)->exists()) {
                        $fail('Esta CNH já consta em outro registro.');
                    }
                }
            ],
            'validade_cnh' => ['required', 'date', 'after:today'], // Proteção contra CNHs já vencidas no onboarding
            'rntrc'        => [
                'required', 'string', 'max:15',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (Motorista::where('rntrc_bidx', $bidx)->exists()) {
                        $fail('Este RNTRC já está associado a outro motorista.');
                    }
                }
            ],
        ]);
    }
}