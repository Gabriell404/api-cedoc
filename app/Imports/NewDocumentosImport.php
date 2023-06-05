<?php

namespace App\Imports;

use App\Models\Documento;
use App\Services\RastreabilidadeService;
use Carbon\Carbon;
use App\Models\TipoDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Imtigger\LaravelJobStatus\Trackable;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Throwable;

class NewDocumentosImport implements ToCollection, WithHeadingRow, ShouldQueue, WithChunkReading, WithEvents
{
    use Importable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public $log = [];
    protected $user;
    protected $rastreabilidadeService;

    public function __construct(
        $user,
        $rastreabilidadeService
    )
    {
        $this->prepareStatus();
        $this->setInput(['status' => 'progress']);
        $this->user = $user;
        $this->rastreabilidadeService = $rastreabilidadeService;
    }


    /**
     * @param array $row
     *
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row)
        {
            $this->incrementProgress();

            $validations = [
                'cliente' => $row['cliente'],
                'cpfcnpj' => $row['cpfcnpj'],
                'documento' => $row['documento'],
                'tipo_documental' => $row['tipo_documental'],
            ];

            $validator = Validator::make($validations, [
                'cliente' => 'required',
                'cpfcnpj' => 'min:12|max:15|required',
                'documento' => 'required',
                'tipo_documental' => 'required|exists:App\Models\TipoDocumento,id',
            ], [
                'cliente.required' => 'Cliente é obrigatório',
                'cpfcnpj.required' => 'CPF/CNPJ é obrigatório',
                'cpfcnpj.min' => 'CPF/CNPJ deve conter no mínimo 12 caracteres',
                'cpfcnpj.max' => 'CPF/CNPJ deve conter no máximo 15 caracteres',
                'documento.required' => 'Número dossiê é obrigatório',
                'tipo_documental.required' => 'Tipo Documental é obrigatório',
                'tipo_documental.exists' => 'O identificador do tipo documental não existe',
            ]);

            if($validator->fails()){
                array_push($this->log, ['documento' => $row['documento'], 'status' => 'Processado com erros:'.implode(',', $validator->errors()->all())]);
                continue;
            }

            $cpfReplace = str_replace('-', '', $row['cpfcnpj']);

            $documento = Documento::updateOrCreate(
                [
                    'documento' => $row['documento'],
                    'tipo_documento_id' => $row['tipo_documental'],
                    'cpf_cooperado' => $cpfReplace,
                ],
                [
                    'nome_cooperado' => $row['cliente'],
                    'vencimento_operacao' => $row['vencimento'] !== "00/01/1900" ? Carbon::createFromFormat('d/m/Y', $row['vencimento']) : null,
                    'valor_operacao' => $row['vlr_operacao'] ?? null,
                    'status' => TipoDocumento::find($row['tipo_documental'])->digital === 1 ? 'arquivado': 'alocar'
                ],
            );

            $this->rastreabilidadeService->create(
                'cadastrar',
                $documento->id,
                $this->user,
                'Registro cadastrado/atualizado via importação de arquivo'
            );

            //array para setar no output do job
            array_push($this->log, ['documento' => $row['documento'], 'status' => 'Registro enviado']);
        }

        $this->setOutput(['registros' => $this->log, 'error' => false]);
        $this->setInput(["status" => "finished"]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $this->setProgressMax((int) $event->getReader()->getTotalRows()["Plan1"]);
            },
            ImportFailed::class => function(ImportFailed $event) {
                $this->setOutput(['registros' => $this->log, 'error' => $event->getException()->getMessage()]);
                $this->setInput(["status" => "error"]);
            },
        ];
    }

    public function chunkSize(): int
    {
        return 1000000;
    }

}
