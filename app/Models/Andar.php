<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Andar extends Model
{
    use HasFactory;

    protected $table = 'andars';

    protected $fillable = [
        'numero',
        'predio_id'
    ];

    public function caixas()
    {
        return $this->hasMany(Caixa::class, 'andar_id', 'id');
    }
}
