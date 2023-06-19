<?php

/**
 * @author Paulo Henrique Alves Batista <paulobatista.sistemas@gmail.com>
 * @copyright 2023 - 2023
 * @version 1.0
 *
 * Não alterar sem consentimento dos autores.
 *
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Resources\Repactuacao\RepactuacaoCollectionResource;
use App\Http\Resources\Documento\DocumentoResource;
use App\Http\Services\CaixaService;
use App\Http\Services\DocumentoService;
use App\Http\Services\RepactuacaoService;
use App\Services\RastreabilidadeService;
use App\Services\ResponseService;
use Carbon\Carbon;
use App\Models\Documento;
use App\Models\Repactuacao;
use App\Models\Unidade;
use Exception;
use Illuminate\Support\Facades\DB;

class RepactuacaoController extends Controller
{

    private $repactuacao;

    public function __construct(
        protected CaixaService $caixaService,
        protected DocumentoService $documentoService,
        protected RepactuacaoService $repactuacaoService,
        Repactuacao $repactuacao,
    )
    {
        $this->repactuacao = $repactuacao;
    }

    /**
     * Listar documentos com status de repactuacao pendente na fila.
     *
     * @param  mixed $id
     * @return \Illuminate\Http\Response
     */

     public function fila(Request $request)
     {
        try {
            $query = Documento::with(['tipoDocumento'])
            ->when($request->get('documento'), function ($query) use ($request) {
                return $query->where('documento', '=', $request->get('documento'));
            })
            ->when($request->get('cpf'), function ($query) use ($request) {
                return $query->where('cpf_cooperado', '=', $request->get('cpf'));
            })
            ->where('status', 'fila_repactuacao')
            ->when($request->get('predio_id'), function ($query) use ($request) {
                $query->where('predio_id', '=', $request->get('predio_id'));
            })->when($request->get('tipo_documento_id'), function ($query) use ($request) {
                return $query->where('tipo_documento_id', '=', $request->get('tipo_documento_id'));
            })->when($request->get('caixa'), function ($query) use ($request) {
                return $query->where('caixa_id', '=', $request->get('caixa'));
            })->when($request->get('ordenar_campo'), function ($query) use ($request) {
                return $query->orderBy(
                    $request->get('ordenar_campo'),
                    $request->get('ordenar_direcao') ?? 'asc'
                );
            }, function ($query) use ($request) {
                return $query->orderBy('ordem');
            })
            ->get()->map(function($item){
                $item->total_espaco = (float) $item->repactuacoes->sum('documento.espaco_ocupado');
                return $item;
            });

            return new RepactuacaoCollectionResource($query);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('documento.show', null, $e);
        }
     }

    /**
     * Selecionar possiveis documentos para repactuação.
     *
     * @param  mixed $id
     * @return \Illuminate\Http\Response
     */

    public function salvar_fila_repactuacao(
        mixed $id
    )
    {
        try {

            DB::beginTransaction();

            $documento = $this->documentoService->findById($id);

            $repactuacao = $this->repactuacaoService->salvar_fila(
                $documento
            );

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'Documento enviado para fila de repactuação'
            ]);

        } catch (\Throwable $th) {

            DB::rollback();

            return ResponseService::exception('documento.espaco_disponivel', null, $th);

        }
    }

    /**
     * Deletar da fila documento.
     *
     * @param  mixed $id
     * @return \Illuminate\Http\Response
     */

     public function remover_fila_repactuacao(
        mixed $id
    )
    {
        try {

            DB::beginTransaction();

            $documento = $this->documentoService->findById($id);

            $this->repactuacaoService->remover_fila(
                $documento
            );

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'Documento deletado da fila de repactuação'
            ]);

        } catch (\Throwable $th) {

            DB::rollback();

            return ResponseService::exception('documento.espaco_disponivel', null, $th);

        }
    }



     /**
     * salvar endereços repactuados.
     *
     * @param  mixed $id
     * @return \Illuminate\Http\Response
     */

     public function enderecar(Request $request)
     {
        try {
            //iniciar um transação de dados
            DB::beginTransaction();

            //receber parametros
            $andar_id = $request->get('andar_id');
            $espaco_ocupado = $request->get('espaco_ocupado');
            $numero_caixa = $request->get('numero_caixa');
            $observacao = $request->get('observacao');
            $predio_id = $request->get('predio_id');
            $documentos = $request->get('documentos');
            $documento_pai_id = (int) $request->get('documento_pai_id');

            $predio_id =  (int) Unidade::getIdPredio(
                $request->get('predio_id')
            );

            $ordem = Documento::ordem($numero_caixa);

            $this->repactuacaoService->enderecar(
                $documentos,
                $numero_caixa,
                $espaco_ocupado,
                $observacao,
                $ordem,
                $predio_id,
                $andar_id,
                $documento_pai_id
            );

            DB::commit();

            return response()->json([
                'error' => false,
                'msg' => 'Documentos endereçados com sucesso'
            ], 200);

        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'error' => true,
                'msg' => 'Ocorreu um erro ao realizar o endereçamento',
                'detalhe' => $e->getMessage()
            ], 500);
        }
     }

     /**
     * Listar todas as repactuacoes.
     *
     * @return \Illuminate\Http\Response
     */
    public function lista(Request $request)
    {
        try {

            $aditivo_id = $request->get('aditivo_id');


            $query = Repactuacao::with(['aditivo.tipoDocumento', 'aditivo.caixa.predio', 'documento.tipoDocumento'])
            ->select('repactuacoes.aditivo_id', 'repactuacoes.documento_id', 'repactuacoes.user_id')
            ->when($aditivo_id, function($query) use ($aditivo_id) {
                $query->where('repactuacoes.aditivo_id', $aditivo_id);
            })
            ->orderBy('repactuacoes.created_at')
            ->paginate(10);

            return new RepactuacaoCollectionResource($query);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('repactuacao.lista', null, $e);
        }
    }
}
