<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caixa extends Model
{
    use HasFactory;

    protected $table = 'caixas';

    protected $withCount = ['documentos'];

    protected $fillable = [
        'status', 'numero', 'espaco_total', 'espaco_ocupado', 'espaco_disponivel', 'predio_id', 'andar_id'
    ];

    public function predio()
    {
        return $this->belongsTo(Unidade::class, 'predio_id', 'id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'caixa_id', 'id');
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $espaco
     * @return \Illuminate\Database\Eloquent\Builder
     */

     public function scopeEspacoDisponivel($query, $espaco){
        return $query->where([['caixas.status', '=' ,'disponivel'], ['caixas.espaco_disponivel', '>=', $espaco]]);
     }
}
