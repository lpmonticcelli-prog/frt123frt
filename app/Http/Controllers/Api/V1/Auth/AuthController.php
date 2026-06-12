<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\ReceitaWSService;
use App\Services\CpfValidatorService;
use App\Services\Security\BlindIndexService;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email', 
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->status === 'banned') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                throw ValidationException::withMessages([
                    'email' => ['Acesso negado. Esta conta foi banida permanentemente da plataforma por violação dos termos de segurança.']
                ]);
            }

            $request->session()->regenerate();

            return response()->json([
                'message' => 'Autenticado com sucesso',
                'user' => $user->load('role')
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => ['Credenciais incorretas.']
        ]);
    }

    public function registerEmbarcador(Request $request, ReceitaWSService $receitaWSService): JsonResponse
    {
        // ZT-DEFENSE: A trava unique do banco foi substituída pela função Closure para checar o Index Cego
        $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|string|email|unique:users,email',
            'password'           => 'required|string|min:8',
            'phone'              => [
                'required', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (User::where('phone_bidx', $bidx)->exists()) {
                        $fail('Este telefone já encontra-se vinculado a outro cadastro.');
                    }
                }
            ],
            'razao_social'       => 'required|string|max:150',
            'cnpj'               => [
                'required', 'string', 'max:18',
                function ($attribute, $value, $fail) {
                    $bidx = BlindIndexService::make($value);
                    if (Embarcador::where('cnpj_bidx', $bidx)->exists()) {
                        $fail('Este CNPJ já está registado na nossa base de dados.');
                    }
                }
            ], 
            'inscricao_estadual' => 'nullable|string|max:30',
        ]);

        $analiseCNPJ = $receitaWSService->validarCNPJ($request->cnpj);
            
        if (!$analiseCNPJ['valido']) {
            return response()->json([
                'message' => 'Validação fiscal falhou na Receita.',
                'errors' => ['cnpj' => [$analiseCNPJ['mensagem']]]
            ], 422);
        }

        return DB::transaction(function () use ($request, $analiseCNPJ) {
            $role = Role::where('slug', 'embarcador')->firstOrFail();

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'phone'    => $request->phone,
                'role_id'  => $role->id,
                'status'   => 'pending'
            ]);

            Embarcador::create([
                'user_id'            => $user->id,
                'razao_social'       => $analiseCNPJ['razao_social'] ?? $request->razao_social,
                'cnpj'               => $request->cnpj,
                'inscricao_estadual' => $request->inscricao_estadual,
            ]);

            Auth::login($user);
            request()->session()->regenerate();

            return response()->json([
                'message' => 'Conta criada e autenticada com sucesso.',
                'user'    => $user->load('role')
            ], 201);
        });
    }

    public function registerMotorista(Request $request, CpfValidatorService $cpfValidator): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
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
                        $fail('Esta CNH já consta noutro registo.');
                    }
                }
            ],
            'validade_cnh' => 'required|date',
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

        if (!$cpfValidator->isValid($request->cpf)) {
            return response()->json([
                'message' => 'Documento rejeitado.',
                'errors' => ['cpf' => ['O CPF informado é matematicamente inválido pela RFB.']]
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $role = Role::where('slug', 'motorista')->firstOrFail();

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'phone'    => $request->phone,
                'role_id'  => $role->id,
                'status'   => 'pending'
            ]);

            Motorista::create([
                'user_id'       => $user->id,
                'cpf'           => $request->cpf,
                'cnh'           => $request->cnh,
                'validade_cnh'  => $request->validade_cnh,
                'rntrc'         => $request->rntrc,
                'is_disponivel' => false,
            ]);

            Auth::login($user);
            request()->session()->regenerate();

            return response()->json([
                'message' => 'Conta submetida com sucesso. Aguardando liberação compliance KYC.',
                'user'    => $user->load('role')
            ], 201);
        });
    }

    public function me(Request $request): JsonResponse 
    { 
        return response()->json($request->user()->load('role')); 
    }
    
    public function logout(Request $request): JsonResponse 
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout executado com sucesso.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return response()->json([
            'message' => 'Se o e-mail constar na nossa base, um link de recuperação foi enviado.'
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'A sua senha foi redefinida com sucesso.']);
        }

        throw ValidationException::withMessages([
            'email' => ['O link de recuperação é inválido ou expirou. Solicite um novo.']
        ]);
    }
}