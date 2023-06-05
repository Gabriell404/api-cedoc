<?php

namespace App\Services;

use App\Models\Rastreabilidade;
use Exception;
use Illuminate\Support\Facades\DB;

class RastreabilidadeService {

    public function create($operacao, $documentoId, $userId, $descricao)
    {
        try{

            DB::beginTransaction();

            Rastreabilidade::create([
                'user_id' => $userId,
                'operacao' => $operacao,
                'documento_id' => $documentoId,
                'descricao' => $descricao
            ]);

            DB::commit();

        }catch(Exception $e){

            DB::rollBack();

            throw $e;
        }
    }

}
