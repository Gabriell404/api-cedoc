<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HistoricoArquivo extends Model
{
    const QUANTIDADE_MAXIMA_CAIXAS_POR_PREDIO = 63;

    use HasFactory;

    public function predio()
    {
        return $this->belongsTo(Unidade::class, 'predio_id', 'id');
    }

    public function andar()
    {
        return $this->belongsTo(Andar::class, 'andar_id', 'id');
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id', 'id');
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $espaco
     * @return \Illuminate\Database\Eloquent\Builder
     */

     public function scopeUltimoHistorico($query){
        return $query
        ->select('*', DB::raw('count(caixa_id) as quantidade_caixas'))
        ->where([
            ['status', '=' ,'arquivado'],
        ])
        ->groupBy('predio_id')
        ->having('quantidade_caixas', '<', 63)
        ->orderByDesc('predio_id', 'andar_id');
     }
}
