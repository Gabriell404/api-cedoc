<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documentos', function (Blueprint $table) {
            DB::statement("ALTER TABLE `documentos` MODIFY column `status_anterior` ENUM('alocar', 'arquivado', 'retirar', 'emprestado', 'liquidado', 'repactuacao', 'fila_repactuacao', 'repactuacao_filho') DEFAULT 'alocar'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
