<?php

namespace App\Http\Requests\Importacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImportacaoRequest extends FormRequest
{
   /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'arquivo' => 'required|mimes:xlsx',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */

     public function messages()
     {
        return [
            'arquivo.required' => 'O arquivo é obrigatório',
            'arquivo.mimes' => 'O tipo de arquivo aceito é no formato XLSX',
        ];
     }

     public function withValidator($validator){

        if($validator->fails()){
            throw new HttpResponseException(response()->json([
                'msg' => 'Ops! Ocorreu um erro ao validar os campos!',
                'status' => false,
                'errors' => $validator->errors(),
                'url' => route('documento.importar_novos')
            ], 403));
        }

    }
}
