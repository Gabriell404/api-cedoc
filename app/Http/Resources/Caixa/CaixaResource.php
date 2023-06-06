<?php

namespace App\Http\Resources\Caixa;

use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CaixaResource extends JsonResource
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
            'espaco_total' => $this->espaco_total,
            'espaco_disponivel' => $this->espaco_disponivel,
            'espaco_ocupado' => $this->espaco_ocupado,
            'unidade' => $this->unidade,
            'endereco' => $this->endereco,
            'documentos_count' => $this->documentos_count,
            'andar_id' => $this->andar_id,
            'predio' => $this->predio,
            'created_at' => Carbon::parse($this->created_at)->format('d/m/Y H:i:s'),
            'updated_at' => Carbon::parse($this->updated_at)->format('d/m/Y H:i:s'),
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
