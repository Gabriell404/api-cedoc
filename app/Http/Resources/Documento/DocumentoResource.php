<?php

namespace App\Http\Resources\Documento;

use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
   private $config;

      /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */

    public function __construct($resource, $config = array())
    {
        parent::__construct($resource);

        $this->config = $config;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'espaco_ocupado' => $this->espaco_ocupado,
            'documento' => $this->documento,
            'documentos_count' => $this->whenCounted('caixa.documentos'),
            'observacao' => $this->observacao,
            'ordem' => $this->ordem,
            'nome_cooperado' => $this->nome_cooperado,
            'cpf_cooperado' => $this->cpf_cooperado,
            'valor_operacao' => $this->valor_operacao,
            'usuario' => $this->usuario,
            'rastreabilidades' => $this->rastreabilidades,
            'vencimento_operacao' => $this->vencimento_operacao ? Carbon::parse($this->vencimento_operacao)->format('d/m/Y') : null,
            'data_liquidacao' => Carbon::parse($this->data_liquidacao)->format('d/m/Y'),
            'data_expurgo' => Carbon::parse($this->data_expurgo)->format('d/m/Y'),
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y H:i:s'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d/m/Y H:i:s'),
            'tipo_documento' => $this->tipoDocumento,
            'caixa' => $this->caixa,
            'predio' => $this->predio,
        ];
    }

     /**
     * Get additional data that should be returned with the resource array.
     *
     * @param \Illuminate\Http\Request  $request
     * @return array
     */

    public function with($request)
    {
        return ResponseService::default($this->config, $this->id);
    }

     /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request
     * @param  \Illuminate\Http\Response
     * @return void
     */

    public function withResponse($request, $response)
    {
        $response->setStatusCode(200);
    }
}
