<?php

namespace App\Http\Services;

use App\Models\Role;
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

    /**
     * Função para retornar descrição de uma regra
     * @param string $role
     * @return string
     */
    public function getRole(string $role): string {
        return Role::where('nome', $role)->first()->descricao;
    }

}
