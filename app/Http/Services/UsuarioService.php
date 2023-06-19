<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UsuarioService {

    /**
     * Função para listar e filtrar usuários
     *
     * @return Collection
     */
    public function listar()
    {
        return User::with(['loginSecurity', 'perfils'])->paginate(10);
    }

    /**
     * Função para buscar usuários
     *
     * @param int $id
     */
    public function findById(int $id)
    {
        return User::findOrFail($id);
    }

}
