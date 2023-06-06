<?php

namespace App\Http\Requests\Caixa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CaixaStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' =>'required|max:191',
            'espaco_total' =>'required',
            'unidade_id' => 'required|exists:App\Models\Unidade,id',
            'endereco_id' => 'required|exists:App\Models\Endereco,id',
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'O campo numero deve ser especificado.',
            'numero.max' => 'O campo numero deve ser no máximo :max.',
            'espaco_total.required' => 'O campo :attribute não pode ser vazio.',
            'unidade_id.required' => 'O campo :attribute não pode ser'
        ];
    }


    public function withValidator($validator){

        if($validator->fails()){
            throw new HttpResponseException(response()->json([
                'msg' => 'Ops! Algum campo não foi preenchido corretamente!',
                'status' => false,
                'errors' => $validator->errors(),
                'url' => route('unidade.store')
            ], 403));
        }

    }
}
