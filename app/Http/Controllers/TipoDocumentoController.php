<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoDocumento\TipoDocumentoStoreRequest;
use App\Http\Requests\TipoDocumento\TipoDocumentoUpdateRequest;
use App\Http\Resources\TipoDocumento\TipoDocumentoCollectionResource;
use App\Http\Resources\TipoDocumento\TipoDocumentoResource;
use App\Models\TipoDocumento;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TipoDocumentoController extends Controller
{
    private $tipoDocumento;

    public function __construct(TipoDocumento $tipoDocumento)
    {
        $this->tipoDocumento = $tipoDocumento;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {

            $query = $this->tipoDocumento->with('usuario')
            ->when($request->get('descricao'), function ($query) use ($request) {
                $query->where('descricao', 'like', '%' . $request->get('descricao') . '%');
            })
            ->when($request->get('ordem'), function ($query) use ($request) {
                $query->orderBy($request->get('ordem'));
            }, function ($query) {
                $query->orderBy('id');
            })->when($request->get('page'), function ($query) use ($request) {
                if($request->get('page') < 0){
                    return $query->get();
                }

                return $query->paginate(12);
            }, function($query){
                return $query->get();
            });


            return new TipoDocumentoCollectionResource($query);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('tipo-documento.show', null, $e);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


      /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {

            if($id === null) {
                throw new Exception('Informe o id do registro.');
            }

            $tipoDocumento = $this->tipoDocumento->find($id);

            if(!$tipoDocumento){
                throw new Exception('Nenhum registro encontrado.');
            }

            return new TipoDocumentoResource($tipoDocumento, ['route' => 'tipo-documento.detalhes', 'type' => 'detalhes']);

        }catch(\Throwable|\Exception $e) {
            return ResponseService::exception('tipo-documento.show', $id, $e);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TipoDocumentoStoreRequest $request)
    {
        try {

            $tipoDocumento = $this->tipoDocumento->create([
                'descricao' => $request->descricao,
                'temporalidade' => $request->temporalidade,
                'user_id' => Auth::user()->id,
                'tipo_temporalidade' => $request->tipo_temporalidade,
                'digital' => (bool) $request->digital
            ]);

            return new TipoDocumentoResource($tipoDocumento, ['route' => 'tipo-documento.store', 'type' => 'store']);

        }catch(\Throwable|\Exception $e) {

            return ResponseService::exception('tipo-documento.store', null, $e);
        }


    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TipoDocumentoUpdateRequest $request, $id)
    {
        try {
            $tipoDocumento = $this->tipoDocumento->find($id);

            if(!$tipoDocumento){
                throw new Exception('Nenhum registro encontrado.');
            }

            $tipoDocumento->update([
                'descricao' => $request->descricao,
                'temporalidade' => $request->temporalidade,
                'user_id' => Auth::user()->id,
                'tipo_temporalidade' => $request->tipo_temporalidade,
                'digital' => (bool) $request->digital
            ]);

            return new TipoDocumentoResource($tipoDocumento, ['route' => 'tipo-documento.update', 'type' => 'update']);

        } catch (\Throwable|\Exception $e) {

            return ResponseService::exception('tipo-documento.update', $id, $e);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $tipoDocumento = $this->tipoDocumento->find($id);

            if(!$tipoDocumento){
                throw new Exception('Nenhum registro encontrado.');
            }

            if($tipoDocumento->documentos->count()){
                throw new Exception('Você não pode excluir esse registro.');
            }

            $tipoDocumento->delete();

            return ResponseService::default(['route' => 'tipo-documento.destroy', 'type' => 'destroy'], $id);

        } catch (\Throwable|\Exception $e) {

            return ResponseService::exception('tipo-documento.destroy', $id, $e);
        }
    }
}
