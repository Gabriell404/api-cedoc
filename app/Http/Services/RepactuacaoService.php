<?php

/**
 * @author Paulo Henrique Alves Batista <paulobatista.sistemas@gmail.com>
 * @copyright 2023 - 2023
 * @version 1.0
 *
 * Não alterar sem consentimento dos autores.
 *
 */

namespace App\Http\Services;
use App\Models\Documento;
use App\Models\Repactuacao;
use App\Http\Services\CaixaService;
use App\Http\Services\DocumentoService;
use App\Models\Caixa;
use App\Services\RastreabilidadeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepactuacaoService {

    public function __construct(
        protected CaixaService $caixaService,
        protected DocumentoService $documentoService,
        protected RastreabilidadeService $rastreabilidadeService,
    ) {}

    /**
     * Função para registrar documento na fila de espera, ou seja, alteração do status e registro de rastro
     *
     * @param Documento $documento
     */
    public function salvar_fila(
        Documento $documento
    )
    {

        try{

            //salvar rastreabilidade do documento
            $this->rastreabilidadeService->create(
                'alterar',
                $documento->id,
                Auth::user()->id,
                'Documento enviado para fila de repactuação'
            );

            //alterar status para fila de repactuação
            $documento->update([
                'status' => 'fila_repactuacao',
                'status_anterior' => $documento->status
            ]);

        }catch(\Exception $e){
            throw new \Error($e->getMessage());
        }

    }

    /**
     * Função para remover o documento da fila
     *
     * @param Documento $documento
     * @param string $status
     */
    public function remover_fila(
        Documento $documento
    )
    {


        try{
            //salvar rastreabilidade do documento
            $this->rastreabilidadeService->create(
                'alterar',
                $documento->id,
                Auth::user()->id,
                'Documento removido da fila de repactuações'
            );

            //alterar status para fila de repactuação
            $documento->update([
                'status' => $documento->status_anterior,
                'status_anterior' => null,
            ]);


        }catch(\Exception $e){


            throw new \Error($e->getMessage());
        }

    }


    /**
     * Função para remover o endereço do documento
     *
     * @param Documento $documento
     * @param string $status
     */
    public function remover_endereco(
        Documento $documento,
        string $status
    )
    {

        $documento->update([
            'caixa_id' => null,
            'predio_id' => null,
            'status' => $status,
            'ordem' => 0
        ]);
    }

    /**
     * Função para endereçar documentos repactuados
     *
     * {@internal O documento pai que está sendo alterado na repactuação irá repetir no relacionamento 'repactuacoes' no loop.
     *            Logo será necessário sair do loop de buscas dos filhos ('continue'), para evitar duplicidade de registros.}
     *
     * {@internal O documento pai ('aditivo_id') será duplicado na tabela repactuacoes, seguir as intruções acima}
     *
     * @param array $documentos
     * @param int $caixa_id
     * @param float $espaco_ocupado
     * @param string|null $observacao
     * @param string|int $ordem
     * @param int $predio_id
     * @param int $andar_id
     * @param int $documento_pai_id
     *
     */

     public function enderecar(
        array $documentos,
        int $caixa_id,
        string $espaco_ocupado,
        string|null $observacao,
        string $ordem,
        int $predio_id,
        int $documento_pai_id,
     )
     {

        try {
            DB::beginTransaction();

            //variabel para calcular espaço dos documentos
            $calculaEspaco = 0;

            foreach ($documentos as $doc) {

                //buscar documento
                $documento = $this->documentoService->findById($doc['id']);


                //descrição documento
                if(!is_null($documento->caixa_id)){
                    $descricao = 'Documento arquivado anteriormente no Prédio '.$documento->predio_id.', Caixa '.$documento->caixa_id.' Ordem '.$documento->ordem;
                }elseif(!is_null($documento->caixa_id) && $documento->id == $documento_pai_id){
                    $descricao = 'Dossiê de Repactuação';
                }else{
                    $descricao = 'Documento não possuía endereçamento anterior, sendo seu primeiro endereço no dossiê de repactuação.';
                }

                // observação do documento
                if(!is_null($observacao) && !is_null($documento->observacao)){
                    $concatObservacao = "Observação Repactuação:".$observacao." \n\n Observação Anterior:".$documento->observacao;
                }else{
                    $concatObservacao = "Sem informações...";
                }


                //verificar se possui repactuações
                if($documento->repactuacoes_count > 0){

                    //percorrer os filhos e alterar o pai
                    foreach ($documento->repactuacoes as $repac_filho) {
                        $repac_filho->aditivo_id = $documento_pai_id;
                        $repac_filho->documento->caixa_id = $caixa_id;
                        $repac_filho->documento->predio_id = $predio_id;
                        $repac_filho->documento->ordem = (int) $ordem;
                        $repac_filho->documento->status = 'repactuacao';
                        $calculaEspaco += $repac_filho->documento->espaco_ocupado;

                        $this->rastreabilidadeService->create(
                            'repactuar',
                            $repac_filho->documento->id,
                            Auth()->user()->id,
                            $descricao
                        );

                        if($repac_filho->documento->status_anterior !== 'alocar' && $repac_filho->documento->status_anterior !== null) {
                            //função para retirar os documentos da caixa de origem
                            $caixa = $this->caixaService->alterar_conteudo_caixa(
                                $repac_filho->documento->getOriginal('caixa_id'),
                                $repac_filho->documento->espaco_ocupado,
                                $repac_filho->documento->getOriginal('predio_id'),
                                $repac_filho->documento->caixa->andar_id,
                                'saida'
                            );
                        }

                        //atualizar o relacionamento com os filhos
                        $repac_filho->pushQuietly();
                    }

                    //proximo loop
                    continue;
                }

                $calculaEspaco += $documento->espaco_ocupado;

                if($documento->status_anterior !== 'alocar') {
                    //função para retirar os documentos da caixa de origem
                    $caixa = $this->caixaService->alterar_conteudo_caixa(
                        $documento->caixa->id,
                        $documento->espaco_ocupado,
                        $documento->caixa->predio_id,
                        $documento->caixa->andar_id,
                        'saida'
                    );
                }

                // rastreabilidade do documento
                $this->rastreabilidadeService->create('repactuar', $documento->id, Auth()->user()->id, $descricao);

                // criar relações da repactuação
                $this->repactuacoes($documento, $documento_pai_id);

                // documentos sem repactuações
                $documento->update([
                    'espaco_ocupado' => $documento->espaco_ocupado,
                    'status' => 'repactuacao',
                    'caixa_id' => $caixa_id,
                    'predio_id' => $predio_id,
                    'observacao' => $concatObservacao,
                    'ordem' => $ordem,
                ]);
            }

            //função para preencher a caixa destino dos documentos
            $caixa = $this->caixaService->alterar_conteudo_caixa(
                $caixa_id,
                $calculaEspaco,
                $predio_id,
                Caixa::find($caixa_id)->andar_id,
                'entrada'
            );

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
     }

     /**
      * Função para cadastrar relacionamento entre documetnos na tabela repactuações
      * @param Documento $documento
      * @param int $documento_pai_id Id do documento que será registrado como aditivo_id para vincular com outros documentos
      */

     public function repactuacoes(
        Documento $documento,
        int $documento_pai_id
     )
     {
        try {

            return Repactuacao::create([
                'user_id' => Auth()->user()->id,
                'documento_id' => $documento->id,
                'aditivo_id' => $documento_pai_id
            ]);

        }catch(\Throwable $th) {
            throw $th;
        }
     }
}
