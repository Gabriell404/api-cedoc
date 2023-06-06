<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\DocumentoService;
use App\Services\ResponseService;
use Exception;

class PredioController extends Controller
{

    public function __construct(
        protected DocumentoService $documentoService,
    )
    {

    }
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
    */

    public function disponiveis(Request $request)
    {
        try{

            $predios = $this->documentoService->prediosDisponiveis();

            return response()->json([
                'error' => false,
                'predios' => $predios
            ]);

        }catch(Exception $e){
            return ResponseService::exception('predio.disponiveis', null, $e);
        }
    }
}
