<?php

namespace App\Http\Controllers;

use App\Http\Resources\Usuario\UsuarioUsuarioCollectionResource;
use App\Http\Services\UsuarioService;
use App\Models\Perfil;
use App\Models\User;
use App\Services\LdapService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Google2FA;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class UserController extends Controller
{
    public function __construct(
        protected UsuarioService $usuarioService
    )
    {

    }
    public function login(Request $request)
    {

        try{

            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);

            if($validator->fails()){
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()
                ], 403);
            }

            $result = LdapService::connect($request->get('email'), $request->get('password'));

            if(!$result){
                return response()->json([
                    'error' => true,
                    'message' => 'Email ou senha inválidos.',
                ], 403);
            }

            $user = User::firstOrCreate([
                'email' => $request->get('email'),
            ], [
                'name' => $result['user'],
                'last_login' => now()
            ]);

            Auth::login($user);
            Auth::user()->update([
                'last_login' => Carbon::now()->toDateTimeString(),
                'ip_login' => $request->getClientIp()
            ]);

            $loginSecurity = $user->loginSecurity()->exists() ? $user->loginSecurity->google2fa_enable : null;
            $roles = $user->perfils[0]->permissoes->map(function($permissao){
                return $permissao->nome;
            });

            return response()->json([
                'error' => false,
                'message' => 'Usuário autenticado com sucesso',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'id' => $user->id,
                    'description' => $result['description'],
                    'doisFatores' => boolval($loginSecurity),
                    'roles' => $roles,
                    'admin' => $user->existeAdmin()
                ],
                'token' => $user->createToken($user->name, count($roles) === 0 ? ['isadmin'] : $roles->toArray())->plainTextToken
            ], 200);

        }catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {

            $attr = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:6'
            ]);

            $user = User::create([
                'name' => $attr['name'],
                'password' => bcrypt($attr['password']),
                'email' => $attr['email']
            ]);

            return response()->json([
                'erro' => false,
                'token' => $user->createToken($user->email, ['user:create', 'user:update'])->plainTextToken
            ], 200);

        }catch(Exception $e){
            return response()->json([
            'error' => true,
               'message' => $e->getMessage()
            ], 500);
        }

    }


    public function logout(Request $request)
    {
        Auth::user()->tokens()->delete();
        // Google2FA::logout();
        Session::flush();

        return response()->json([
            'error' => false,
           'message' => 'Usuário deslogado com sucesso'
        ], 200);
    }

    /**
     * Função para listar os usuários do sistema
     *
     *
     */
    public function listar(Request $request)
    {
        try{

            $usuarios = $this->usuarioService->listar();

            return new UsuarioUsuarioCollectionResource($usuarios);

        }catch(Exception $e){

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Função para adicionar ou atualizar Perfil do usuário
     * @param Request $request
     * @param int|string $id
     *
     *
     */
    public function salvarPerfil(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $usuario = $this->usuarioService->findById($id);

            if($usuario->perfils->count()){
                $usuario->removePerfil(
                    $usuario->perfils[0]
                );
            }

            $perfil = Perfil::findOrFail($request->get('perfil'));

            $usuario->adicionaPerfil($perfil);

            DB::commit();

            return response()->json([
               'error' => false,
               'message' => 'Perfil atualizado com sucesso'
            ], 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Função para desabilitar o login seguro com 2 fator de autenticação
     *
     * @param number|string $id
     */
     public function disableLogin(int|string $id)
     {
        try{

            DB::beginTransaction();

            $usuario = $this->usuarioService->findById($id);

            $usuario->loginSecurity->google2fa_enable = 0;
            $usuario->push();

            DB::commit();

            return response(null, 200);

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
     }

}
