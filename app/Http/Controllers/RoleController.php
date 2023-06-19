<?php

namespace App\Http\Controllers;

use App\Http\Resources\Roles\RolesCollectionResource;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Função para criar uma nova regra de acesso do sistema
     *
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function listar()
    {
        try{

            return new RolesCollectionResource(Role::paginate(10));

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
    public function create(Request $request)
    {
        try{

            DB::beginTransaction();

            Role::create([
                'nome' => $request->get('nome'),
                'descricao' => $request->get('descricao'),
            ]);

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Regra criada com sucesso'
            ], 200);

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
