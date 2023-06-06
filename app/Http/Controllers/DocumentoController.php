<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoStoreRequest;
use App\Http\Requests\Importacao\ImportacaoRequest;
use App\Http\Resources\Documento\DocumentoCollectionResource;
use App\Http\Resources\Documento\DocumentoResource;
use App\Http\Resources\ImportacaoCollectionResource;
use App\Http\Resources\ImportacaoResource;
use App\Http\Services\CaixaService;
use App\Http\Services\TipoDocumentoService;
use App\Http\Services\DocumentoService;
use App\Imports\NewDocumentosImport;
use App\Jobs\ProcessImportDossie;
use App\Models\Caixa;
use App\Models\Documento;
use App\Models\JobStatus;
use App\Models\Unidade;
use App\Services\RastreabilidadeService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class DocumentoController extends Controller
{
    use DispatchesJobs;

    private $documento;
    const API_COOPERADO = 'http://10.54.56.236:3000/cooperado/';

    public function __construct(
        Documento $documento,
        protected CaixaService $caixaService,
        protected DocumentoService $documentoService,
        protected TipoDocumentoService $tipoDocumentoService,
        protected RastreabilidadeService $rastreabilidadeService,
    )
    {
        $this->documento = $documento;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     */
    public function index(Request $request)
    {
        try {

            $query = $this->documento->with(['tipoDocumento', 'caixa.predio', 'usuario'])
            ->withCount('repactuacoes')
            ->when($request->get('documento'), function ($query) use ($request) {
                return $query->where('documento', '=', $request->get('documento'));
            })
            ->when($request->get('cpf'), function ($query) use ($request) {
                return $query->where('cpf_cooperado', '=', $request->get('cpf'));
            })
            ->when($request->get('status'), function ($query) use ($request) {
                if(\is_array($request->get('status'))){
                    if(in_array('repactuacao', $request->get('status'))){
                        $status = Arr::where($request->get('status'), fn($value, $key) => $value !== 'repactuacao');
                        return $query->whereHas('repactuacoes')->orWhereIn('status', $status);
                    }
                    $query->whereIn('status', $request->get('status'));
                    return $query;
                }
                return $query->where('status', $request->get('status'));
            })->when($request->get('predio_id'), function ($query) use ($request) {
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
            ->when($request->get('page'), function ($query) use($request){
                if($request->get('page') < 0){
                    return $query->get();
                }
                return $query->paginate(10);
            });

            return new DocumentoCollectionResource($query);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('documento.show', null, $e);
        }
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importar(Request $request)
    {
        try {

            $file = $request->file('arquivo')->storeAs('public', 'importDossie.xlsx');

            $batch = Bus::batch([
                new ProcessImportDossie('storage/importDossie.xlsx'),
            ])->dispatch();

            return response()->json([
                'message' => 'Arquivo enviado com sucesso',
                'batch' => $batch->id,
            ], 200);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('documento.show', null, $e);
        }

    }

    public function progress_batch()
    {

        try {

            $batchs = JobStatus::
            select('id', 'progress_now', 'progress_max', 'input', 'created_at', 'updated_at', DB::raw('(progress_now / progress_max * 100) as progress_percent'))
            ->orderBy('id', 'desc')
            ->paginate(10);

            $batchs->getCollection()->transform(function  (JobStatus $item) {
                return [
                    'id' => $item->id,
                    'progress_now' => $item->progress_now,
                    'progress_max' => $item->progress_max,
                    'input' => json_decode($item->input),
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'progress_percent' => floatval($item->progress_percent),
                ];
            });

            return new ImportacaoCollectionResource($batchs, ['type' => 'show', 'route' => 'documento.importar.progress']);

        } catch (\Throwable|Exception $e) {
            return ResponseService::exception('documento.importar.progress', null, $e);
        }

    }

    public function buscar_progress_batch(Request $request, $id)
    {

        try {

            $batchs = JobStatus::find($id);
            $status = json_decode($batchs->input);
            $output = json_decode($batchs->output);
            $filtro = $request->get('filter_output');

            //verificar se existe filtro no processamento de dados do arquivo
            if($request->get('filter_output') != 'todos' && $status->status === "finished"){
                $array = array_filter($output->registros, function($item) use ($request) {
                    return str_contains($item->status, $request->get('filter_output'));
                });

                $batchs->output = json_encode(["registros" => $array, "error" => false]);
            }

            return new ImportacaoResource($batchs, ['type' => 'show', 'route' => 'documento.importar.progress.buscar']);


        } catch (\Throwable|Exception $e) {
            return ResponseService::exception('documento.importar.progress', null, $e);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importar_novos(ImportacaoRequest $request)
    {
        try {

            $headings = (new HeadingRowImport)->toArray($request->file('arquivo'));
            $headingsImports = array('cliente', 'cpfcnpj', 'documento', 'vlr_operacao', 'tipo_documental', 'vencimento');
            $diff = array_diff($headingsImports, $headings[0][0]);

            if($diff){
                throw new Exception("Não encontramos as colunas:".implode(",", $diff));
            }

            (new NewDocumentosImport($request->user()->id, $this->rastreabilidadeService))->import($request->file('arquivo'), 'public', \Maatwebsite\Excel\Excel::XLSX);

            return response()->json([
                'message' => 'Arquivo enviado com sucesso',
            ], 200);

        } catch (\Throwable|Exception|\Maatwebsite\Excel\Validators\ValidationException $e) {

            return ResponseService::exception('documento.show', null, $e);
        }

    }

    public function buscar_progress_now($id)
    {

        try {

            $batchs = JobStatus::
            select(DB::raw('(progress_now / progress_max * 100) as progress_percent'))
            ->where('id', $id)
            ->first();

            return response()->json([
                'batch' => $id,
                'progress_now' => $batchs->progress_percent
            ]);

        } catch (\Throwable|Exception $e) {
            return ResponseService::exception('documento.importar.progress', null, $e);
        }

    }

    

     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {

            $documento = $this->documento
                        ->with(['tipoDocumento', 'caixa.predio' , 'caixa', 'rastreabilidades'])
                        ->find($id);

            return new DocumentoResource($documento, ['type' => 'detalhes', 'route' => 'documento.detalhes', 'id' => $id]);

        } catch (\Throwable|Exception $e) {
            return ResponseService::exception('documento.detalhes', $id, $e);
        }
    }

    /**
     * Buscar espaço disponivel para endereçamento.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function buscar_enderecamento(Request $request)
    {
        try{
            $espaco_ocupado = (float) $request->get('espaco_ocupado');
            $tipo_documento_id = $request->get('tipo_documento_id');
            $cpf_cooperado = $request->get('cpf_cooperado');
            $numero = $request->get('numero');
            $page = $request->get('page');
            $predio_id = $request->get('predio_id');

            //pegar informações do documento a ser endereçado
            $documentos = $this->documentoService->getDocumento($tipo_documento_id, $cpf_cooperado, $numero, $page);

            if(!$documentos){
                throw new \Error('Não localizamos nenhum documento', 404);
            }


            //ultima caixa lançada no sistema por ordem de numero (número é unico e ordem descrescente)
            $ultima_caixa = $this->caixaService->ultimaCaixa();

            $espaco_predio = $this->documentoService->espacoDisponivelPredio($ultima_caixa);

            //pegar proximo endereço
            $proximo_endereco = $this->documentoService->proximoEndereco(
                $espaco_ocupado
            );

            //caixas que possuem espaço disponivel para ser armazenado
            $caixas = $this->caixaService->espacoDisponivel($espaco_ocupado, $predio_id);

            //pegar os ids dos predios que possuem espaço disponivel
            $predios_disponiveis = $this->documentoService->prediosDisponiveis();

            return response()->json([
                'documentos' => $documentos,
                'proximo_endereco' => $proximo_endereco,
                'ultima_caixa' => $ultima_caixa,
                'predio' => $espaco_predio,
                'caixas' => $caixas,
                'espaco_ocupado' => $espaco_ocupado,
                'predios_disponiveis' => $predios_disponiveis,
            ]);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('documento.espaco_disponivel', null, $e);

        }
    }

    /**
     * Salvar endereço em um documento.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function proximo_endereco(Request $request)
     {

        try{

            $espaco_ocupado = (float) $request->get('espaco_ocupado');

            //pegar proximo endereço
            $proximo_endereco = $this->documentoService->proximoEndereco(
                $espaco_ocupado
            );

            $proximo_endereco->espaco_ocupado_documento = $espaco_ocupado;

            return response()->json([
                'proximo_endereco' => $proximo_endereco
            ], 200);

        }catch(Exception $e){
            return ResponseService::exception('documento.espaco_disponivel', null, $e);
        }

     }

     /**
     * Função para salvar endereçamento de um documento.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function salvar_enderecamento(Request $request)
    {
        try {
            //iniciar um transação de dados
            DB::beginTransaction();

            //atributos somente na pagina de enderecamento
            $documentoId = $request->get('id');
            $numero_caixa = $request->get('numero_caixa');
            $andar_id = $request->get('andar_id');

            //atributo virá tanto do novo dossie ou na pagina de endereçamento
            $numero_documento = $request->get('documento');
            $espaco_ocupado = (float) $request->get('espaco_ocupado');
            $observacao = $request->get('observacao');
            //atributo virá tanto do novo dossie ou na pagina de endereçamento

            //pegar informações do documento a ser endereçado
            $documento = Documento::find($documentoId);

            if(!$documento){
                //para endereçamento manual irá cair nessa regra
                $nome_cooperado = $request->get('nome');
                $cpf_cooperado = $request->get('cpf');
                $valor_operacao = $request->get('valor');
                $vencimento_operacao = $request->get('vencimento');
                $tipo_documento_id = $request->get('tipo_documento_id');

                $documento = $this->documentoService->create(
                    $numero_documento,
                    $tipo_documento_id,
                    $nome_cooperado,
                    $cpf_cooperado,
                    $vencimento_operacao,
                    $valor_operacao,
                    Auth::user()->id
                );
            }

            //pegar proximo endereço
            $proximo_endereco = $this->documentoService->proximoEndereco(
                $espaco_ocupado
            );


            $predio_id = Unidade::getIdPredio(
                is_null($request->get('predio_id')) ? $proximo_endereco->predio_id : $request->get('predio_id')
            );

            $ordem = Documento::ordem(is_null($numero_caixa) ? $proximo_endereco->caixa_id : $numero_caixa);

            $documentoEnderecado = $this->documentoService->enderecar(
                is_null($numero_caixa) ? $proximo_endereco->caixa_id : $numero_caixa,
                $documento,
                $espaco_ocupado,
                $observacao,
                $ordem,
                $predio_id,
                is_null($andar_id) ? $proximo_endereco->andar_id : Caixa::find($numero_caixa)->andar_id,
            );

            return response()->json([
                'status' => true,
                'msg' => 'Documento salvo com sucesso!',
                'documento' => [
                    'ordem' => $ordem,
                    'predio' => $predio_id,
                    'andar' => is_null($andar_id) ? $proximo_endereco->andar_id : Caixa::find($numero_caixa)->andar_id,
                    'caixa' => is_null($numero_caixa) ? $proximo_endereco->caixa_id : $numero_caixa
                ]
            ], 200);

            //comit de trsanações
            DB::commit();

        } catch (\Throwable|Exception $e) {

            //estorna as trnsações temporarias
            DB::rollBack();

            return ResponseService::exception('documento.espaco_disponivel', null, $e);

        }
    }

    public function filtro(Request $request)
    {
        try {

            $predio = $request->get('predio_id');
            $espaco_ocupado = $request->get('espaco_ocupado');

            $caixas = $this->caixaService->espacoDisponivelManual($espaco_ocupado, $predio);

             return response()->json([
                'caixas' => $caixas
             ]);

        } catch (\Exception $e) {
            return ResponseService::exception('documento.filtro', null, $e);
        }
    }

    /**
     * Função para alterar o tipo documental
     *
     * @param int|string $id
     *
     */
    public function alterar_tipo_documental(int|string $id, Request $request)
    {
        try {

            $documento = $this->documentoService->findById($id);
            $tipoDocumento = $this->tipoDocumentoService->findById($request->get('tipo_documento_id'));

            $this->documentoService->alterar_tipo_documental(
                $documento,
                $tipoDocumento
            );

             return response()->json([
                'error' => false,
                'message' => 'Tipo documental alterado com sucesso!'
             ]);

        } catch (\Exception $e) {
            return ResponseService::exception('documento.editar.tipo_documental', $id, $e);
        }
    }


     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function espaco_ocupado($id, Request $request)
    {
        try{

            $documento = $this->documentoService->findById($id);

            if($this->documentoService->alterar_espaco_ocupado($documento, $request->get('espaco_ocupado'))){
                return response()->json([
                    'error' => false,
                    'msg' => 'Espaço alterado com sucesso!'
                ]);
            };

        } catch (\Throwable|Exception $e) {
            return ResponseService::exception('documento.editar.espaco_ocupado', null, $e);
        }
    }
    
}