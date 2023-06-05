<?php

namespace App\Http\Requests\TipoDocumento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TipoDocumentoStoreRequest extends FormRequest
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
            'descricao' => 'required|max:191',
            'temporalidade' => 'required',
            'tipo_temporalidade' => 'required',
            'digital' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => 'o campo :attribute é obrigatório',
            'descricao.max' => 'o campo :attribute pode conter no máximo :max caracteres',
            'temporalidade.required' => 'o campo :attribute é obrigatório',
            'tipo_temporalidade.required' => 'o campo :attribute é obrigatório',
            'digital' => 'o campo :attribute é obrigatório',
        ];
    }

    public function withValidator($validator){

        if($validator->fails()){
            throw new HttpResponseException(response()->json([
                'msg' => 'Ops! Algum campo não foi preenchido corretamente!',
                'status' => false,
                'errors' => $validator->errors(),
                'url' => route('tipo-documento.store')
            ], 403));
        }

    }
}
