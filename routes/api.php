<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {


// routes tipoDocumento
Route::get('/tipo-documento', [TipoDocumentoController::class, 'index'])->name('tipo-documento.show');
Route::get('/tipo-documento/{id}', [TipoDocumentoController::class, 'show'])->name('tipo-documento.detalhes');
Route::put('/tipo-documento/{id}', [TipoDocumentoController::class, 'update'])->name('tipo-documento.update');
Route::delete('/tipo-documento/{id}', [TipoDocumentoController::class, 'destroy'])->name('tipo-documento.destroy');
Route::post('/tipo-documento', [TipoDocumentoController::class, 'store'])->name('tipo-documento.store');

// routes documento
Route::get('/documento', [DocumentoController::class, 'index'])->name('documento.show');

Route::post('/documento/importar', [DocumentoController::class, 'importar'])->name('documento.importar');
Route::post('/documento/importar/novos', [DocumentoController::class, 'importar_novos'])->name('documento.importar_novos');
Route::get('/documento/importar/progress', [DocumentoController::class, 'progress_batch'])->name('documento.importar.progress');
Route::get('/documento/importar/progress/{id}', [DocumentoController::class, 'buscar_progress_batch'])->name('documento.importar.progress.buscar');
Route::get('/documento/importar/now/{id}', [DocumentoController::class, 'buscar_progress_now'])->name('documento.importar.progress.now');
Route::post('/documento/espaco-ocupado/{id}', [DocumentoController::class, 'espaco_ocupado'])->name('documento.editar.espaco_ocupado');
Route::patch('/documento/tipo-documento/{id}', [DocumentoController::class, 'alterar_tipo_documental'])->name('documento.editar.tipo_documental');


});

// rotas de login e tokens
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'register'])->name('register');

