<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUnidadeRequest;
use App\Http\Requests\Unidade\UnidadeStoreRequest;
use App\Http\Requests\Unidade\UnidadeUpdateRequest;
use App\Http\Resources\Unidade\UnidadeCollectionResource;
use App\Http\Resources\Unidade\UnidadeResource;
use App\Models\Andar;
use App\Models\Unidade;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnidadeController extends Controller
{

    private $unidade;

    public function __construct(Unidade $unidade)
    {
        $this->unidade = $unidade;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {

            $query = $this->unidade
                    ->when($request->get('nome'), function ($query) use ($request) {
                        $query->where('nome', 'like', '%'.$request->get('nome').'%');
                    })
                    ->when($request->get('status'), function ($query) use ($request) {
                        $query->where('status', '=', $request->get('status'));
                    })
                    ->when($request->get('ordem'), function($query) use ($request) {
                    $query->orderBy($request->get('ordem'));
                    }, function ($query){
                    $query->orderBy('id');
                    })->when($request->get('page'), function ($query) use($request){
                    if($request->get('page') < 0){
                        return $query->get();
                    }
                    return $query->paginate(10);
                    });


            return new UnidadeCollectionResource($query);

        } catch (\Throwable|Exception $e) {

            return ResponseService::exception('unidade.show', null, $e);
        }

    }
}