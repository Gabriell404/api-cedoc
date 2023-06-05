<?php

namespace App\Http\Resources;

use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportacaoResource extends JsonResource
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
            'progress_now' => $this->progress_now,
            'progress_max' => $this->progress_max,
            'input' => json_decode($this->input),
            'output' => json_decode($this->output),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'progress_percent' => floatval($this->progress_percent),
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
