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
}