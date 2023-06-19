<?php

namespace App\Http\Services;

use App\Models\Caixa;
use App\Models\Unidade;
use Exception;

class CaixaService {

     /**
     * Função que retorna a ultima caixa
     *
     * @return Caixa
    */
    public function ultimaCaixa()
    {
        try {
            return Caixa::orderByDesc('caixas.id')->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Função para consulta de caixas com espaço disponivel
     *
     * @param float $espaco_ocupado
     * @param int|string $predio_id
     * @return Illuminate\Database\Eloquent\Collection
    */
    public function espacoDisponivel($espaco_ocupado, $predio_id)
    {
        try {
            return Caixa::
            with(['predio','documentos'])
            ->leftjoin('documentos', function ($join) {
                $join->on('documentos.caixa_id', '=', 'caixas.id');
            })
            ->selectRaw('IF(ISNULL(MAX(documentos.ordem) + 1), "1", (MAX(documentos.ordem) + 1)) as proxima_ordem')
            ->espacoDisponivel($espaco_ocupado)
            ->when($predio_id, function ($query) use ($predio_id) {
                $query->where('caixas.predio_id', $predio_id);
            })
            ->whereNot('caixas.id', $this->ultimaCaixa()->id)
            ->groupBy('caixas.id')
            ->orderBy('caixas.id', 'desc')
            ->paginate(8);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /**
     * Função para alterar o espaço da caixa ao arquivar
     *
     *
     * @param int $caixaId
     * @param int $espaco_ocupado
     * @param int $predio_id
     * @param int $andar_id
     * @param string $operacao Variável responsável por informar se operação de arquivamento ou retirada da caixa
     *                         definido por 'entrada' ou 'saida'
     *
     * @return Caixa
     */
    public function alterar_conteudo_caixa(
        int $caixaId,
        float $espaco_ocupado,
        int $predio_id,
        int $andar_id,
        string $operacao
    ): Caixa
    {
        //verifica se a caixa existe / se não cria uma caixa nova
        $caixa = Caixa::find($caixaId);


        if($caixa){
            //alterar caixa
            $espaco_ocupado_caixa = $operacao === 'entrada' ? $espaco_ocupado + (float) $caixa->espaco_ocupado : (float) $caixa->espaco_ocupado - (float) $espaco_ocupado;
            $espaco_disponivel_caixa = $operacao === 'entrada' ? (float) $caixa->espaco_disponivel - (float) $espaco_ocupado : (float) $caixa->espaco_disponivel + (float) $espaco_ocupado;

            $caixa->update([
                'espaco_ocupado' => $espaco_ocupado_caixa,
                'espaco_disponivel' => $espaco_disponivel_caixa,
                'status' => $espaco_disponivel_caixa == 0 ? 'ocupado' : 'disponivel',
            ]);

        }else{
            //criar caixa
            $caixa = Caixa::create(
                [
                    'numero' => $caixaId,
                    'espaco_total' => 13,
                    'espaco_ocupado' => $espaco_ocupado,
                    'espaco_disponivel' => 13 - $espaco_ocupado,
                    'predio_id' => $predio_id,
                    'andar_id' => $andar_id,
                ]
            );
        }

        return $caixa;
    }


    public function espacoDisponivelManual($espaco_ocupado, $predio_id)
    {
        return Caixa::
                    leftjoin('documentos', function ($join) {
                        $join->on('documentos.caixa_id', '=', 'caixas.id');
                    })
                    ->selectRaw('IF(ISNULL(MAX(documentos.ordem) + 1), "1", (MAX(documentos.ordem) + 1)) as proxima_ordem')
                    ->espacoDisponivel($espaco_ocupado)
                    ->when($predio_id, function ($query) use ($predio_id) {
                        $query->where('caixas.predio_id', $predio_id);
                    })
                    ->whereNot('caixas.id', $this->ultimaCaixa()->id)
                    ->groupBy('caixas.id')
                    ->orderBy('caixas.id', 'desc')
                    ->get();
    }

}
