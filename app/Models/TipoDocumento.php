<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipo_documentos';

    protected $fillable = [
        'descricao', 'temporalidade', 'user_id', 'digital', 'tipo_temporalidade'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
