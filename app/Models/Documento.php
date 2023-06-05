<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $withCount = ['repactuacoes'];

    protected $fillable = [
        'documento',
        'observacao',
        'tipo_documento_id',
        'caixa_id',
        'predio_id',
        'espaco_ocupado',
        'status',
        'nome_cooperado',
        'cpf_cooperado',
        'valor_operacao',
        'vencimento_operacao',
        'data_liquidacao',
        'data_expurgo',
        'ordem',
        'user_id',
        'status_anterior'
    ];

    public function caixa()
    {
        return $this->belongsTo(Caixa::class, 'caixa_id', 'id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id', 'id');
    }

    public function predio()
    {
        return $this->belongsTo(Unidade::class, 'predio_id', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function rastreabilidades()
    {
        return $this->hasMany(Rastreabilidade::class, 'documento_id', 'id');
    }

    public function repactuacoes()
    {
        return $this->hasMany(Repactuacao::class, 'aditivo_id', 'id');
    }

    public function sumRepactuacoes()
    {
        return $this->hasMany(Repactuacao::class, 'aditivo_id', 'id');
    }

     /**
     * Scope a query to only include users of a given type.
     *
     * @param  mixed  $caixa_id
     * @return integer
     */

     public function scopeOrdem($query, $caixa_id){
        try {
            $ultima_ordem = $query->where('caixa_id', $caixa_id)->orderBy('ordem', 'desc')->first()->ordem;

            if($ultima_ordem > 0){
                return $ultima_ordem + 1;
            }else{
                return 1;
            }
        } catch (\Throwable $th) {
            return 1;
        }

     }


}
