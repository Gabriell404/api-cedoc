<?php

namespace App\Http\Controllers;

use App\Http\Resources\Perfil\PerfilCollectionResource;
use App\Models\Perfil;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    /**
     * Função para criar uma nova regra de acesso do sistema
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function listar()
    {
        try{

            return new PerfilCollectionResource(Perfil::paginate(10));

        }catch(Exception $e){
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Função para criar uma nova regra de acesso do sistema
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function criar(Request $request)
    {
        try{

            DB::beginTransaction();

            Perfil::create([
                'nome' => $request->get('nome'),
                'descricao' => $request->get('descricao'),
            ]);

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Perfil criado com sucesso'
            ]);

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Função para adicionar um novo Perfil ao usuário
     * @param Request $request
     * @param int|string $id
     *
     *
     */
    public function permissao(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $perfil = Perfil::findOrFail($id);
            $permissao = Role::findOrFail($request->get('permissao'));
            $message = '';

            if(User::existePermissao($permissao->id, $perfil->id)){
                $perfil->removerPermissao($permissao);
                $message = $permissao->nome. ' removida com sucesso';
            }else{
                $perfil->adicionarPermissao($permissao);
                $message = $permissao->nome. ' adicionada com sucesso';
            }

            DB::commit();

            return response()->json([
               'error' => false,
               'message' => $message
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
     * Função para adicionar um novo Perfil ao usuário
     * @param Request $request
     * @param int|string $id
     *
     *
     */
    public function listarPermissao(Request $request, $id)
    {
        try {

            $perfil = Perfil::findOrFail($id);

            return response()->json([
               'data' => $perfil->permissoes
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
