<?php

namespace App\Http\Services;

use App\Models\TipoDocumento;
use Illuminate\Support\Facades\DB;

class TipoDocumentoService {

    public function findById(int|string $id)
    {
        return TipoDocumento::findOrFail($id);
    }
}
