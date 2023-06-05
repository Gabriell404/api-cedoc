<?php

namespace App\Imports;

use App\Models\Documento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DocumentosImport implements ToCollection, WithHeadingRow
{
    /**
    * @param array $row
    *
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
           $documenot = Documento::where('documento', $row['documento'])->first();

           if($documenot){
            return $documenot->update([
                'status' => 'liquidado',
                'data_liquidacao' => Carbon::parse($row['data_liquidacao']),
            ]);
           }

           return null;
        }
    }
}
