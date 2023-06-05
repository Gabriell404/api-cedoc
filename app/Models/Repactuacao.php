<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repactuacao extends Model
{
    use HasFactory;

    protected $table = 'repactuacoes';

    protected $fillable = [
        'documento_id', 'user_id', 'aditivo_id'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function aditivo()
    {
        return $this->belongsTo(Documento::class, 'aditivo_id', 'id');
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id', 'id');
    }

}
