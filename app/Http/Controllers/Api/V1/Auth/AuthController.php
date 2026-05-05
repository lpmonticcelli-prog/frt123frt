<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\ReceitaWSService; // IMPORTAÇÃO DO SERVIÇO DE VALIDAÇÃO DE CNPJ
use App\Services\CpfValidatorService; // IMPORTAÇÃO DO NOVO SERVIÇO DE VALIDAÇÃO DE CPF

class AuthController extends Controller
{
    public function login(Request $request)
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

    public function registerEmbarcador(Request $request, ReceitaWSService $receitaWSService)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'email'              => 'required|string|email|unique:users',
            'password'           => 'required|string|min:8',
            'phone'              => 'required|string|max:20', 
            'razao_social'       => 'required|string|max:150',
            'cnpj'               => 'required|string|max:18|unique:embarcadores', 
            'inscricao_estadual' => 'nullable|string|max:30',
        ], [
            'cnpj.unique' => 'Este CNPJ já está registado na nossa base de dados.'
        ]);

        // =========================================================
        // BLINDAGEM ZERO TRUST: Validação na Receita Federal no ato do Cadastro
        // =========================================================
        $analiseCNPJ = $receitaWSService->validarCNPJ($request->cnpj);
            
        if (!$analiseCNPJ['valido']) {
            // Retorna o erro no formato exato que o Vue.js espera para exibir debaixo do input
            return response()->json([
                'message' => 'Validação de CNPJ falhou.',
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
                // Utilizamos a Razão Social oficial retornada pela Receita Federal para garantir integridade
                'razao_social'       => $analiseCNPJ['razao_social'] ?? $request->razao_social,
                'cnpj'               => $request->cnpj,
                'inscricao_estadual' => $request->inscricao_estadual,
            ]);

            // Autentica o utilizador automaticamente após o registo e injeta os cookies (SPA)
            Auth::login($user);
            request()->session()->regenerate();

            return response()->json([
                'message' => 'Conta criada e autenticada com sucesso. CNPJ validado.',
                'user'    => $user->load('role')
            ], 201);
        });
    }

    // =========================================================
    // ATUALIZAÇÃO CIRÚRGICA: Injeção do CpfValidatorService
    // =========================================================
    public function registerMotorista(Request $request, CpfValidatorService $cpfValidator)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|unique:users',
            'password'     => 'required|string|min:8|confirmed', // Adicionado 'confirmed' para bater com o Vue
            'phone'        => 'required|string|max:20',
            'cpf'          => 'required|string|max:14|unique:motoristas',
            'cnh'          => 'required|string|max:20|unique:motoristas',
            'validade_cnh' => 'required|date',
            'rntrc'        => 'required|string|max:15|unique:motoristas',
        ], [
            'cpf.unique'   => 'Este CPF já está cadastrado.',
            'cnh.unique'   => 'Esta CNH já consta noutro registo.',
            'rntrc.unique' => 'Este RNTRC já está associado a outro motorista.',
        ]);

        // BLINDAGEM ZERO TRUST: Validação Matemática de CPF
        if (!$cpfValidator->isValid($request->cpf)) {
            return response()->json([
                'message' => 'Documento inválido.',
                'errors' => ['cpf' => ['O CPF informado é matematicamente inválido.']]
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
                'message' => 'Conta criada e autenticada com sucesso. CPF validado.',
                'user'    => $user->load('role')
            ], 201);
        });
    }

    public function me(Request $request) { 
        return response()->json($request->user()->load('role')); 
    }
    
    public function logout(Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout executado com sucesso.']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        return response()->json([
            'message' => 'Se o e-mail constar na nossa base, um link de recuperação foi enviado.'
        ]);
    }

    public function resetPassword(Request $request)
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