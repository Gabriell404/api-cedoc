<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rastreabilidade extends Model
{
    use HasFactory;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['usuario'];

    /**
     * The table references.
     *
     * @var string
     */
    protected $table = 'rastreabilidades';

     /**
     * The attributes model fillable.
     *
     * @var array
     */
    protected $fillable = [
        'operacao', 'documento_id', 'user_id', 'descricao'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id', 'id');
    }

}
