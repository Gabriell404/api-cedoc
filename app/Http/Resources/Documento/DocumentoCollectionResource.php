<?php

namespace App\Http\Resources\Documento;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentoCollectionResource extends ResourceCollection
{
    /**
   * Create a new resource instance.
   *
   * @param  mixed  $resource
   * @return void
  */
  public function toArray($request)
  {
    return ['data' => $this->collection];
  }

  /**
   * Get additional data that should be returned with the resource array.
   *
   * @param \Illuminate\Http\Request  $request
   * @return array
  */
  public function with($request)
  {
        return [
        'status' => true,
        'msg'    => 'Listando dados',
        'url'    => route('documento.show')
        ];
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

   /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();
    }
}
