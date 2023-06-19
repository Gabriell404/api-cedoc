<?php

use App\Http\Controllers\CaixaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PredioController;
use App\Http\Controllers\RepactuacaoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

 // routes unidades
 Route::get('/unidade', [UnidadeController::class, 'index'])->name('unidade.show');

// routes tipoDocumento
Route::get('/tipo-documento', [TipoDocumentoController::class, 'index'])->name('tipo-documento.show');
Route::get('/tipo-documento/{id}', [TipoDocumentoController::class, 'show'])->name('tipo-documento.detalhes');
Route::put('/tipo-documento/{id}', [TipoDocumentoController::class, 'update'])->name('tipo-documento.update');
Route::delete('/tipo-documento/{id}', [TipoDocumentoController::class, 'destroy'])->name('tipo-documento.destroy');
Route::post('/tipo-documento', [TipoDocumentoController::class, 'store'])->name('tipo-documento.store');

// routes documento
Route::get('/documento', [DocumentoController::class, 'index'])->name('documento.show');
Route::get('/documento/espaco-disponivel', [DocumentoController::class, 'buscar_enderecamento'])->name('documento.espaco_disponivel');
Route::get('/documento/proximo-endereco', [DocumentoController::class, 'proximo_endereco'])->name('documento.proximo_endereco');
Route::get('/documento/enderecar/filtro', [DocumentoController::class, 'filtro'])->name('documento.filtro');
Route::post('/documento/enderecar', [DocumentoController::class, 'salvar_enderecamento'])->name('documento.salvar_enderecamento');
Route::get('/documento/{id}', [DocumentoController::class, 'show'])->name('documento.detalhes');
Route::post('/documento/importar', [DocumentoController::class, 'importar'])->name('documento.importar');
Route::post('/documento/importar/novos', [DocumentoController::class, 'importar_novos'])->name('documento.importar_novos');
Route::get('/documento/importar/progress', [DocumentoController::class, 'progress_batch'])->name('documento.importar.progress');
Route::get('/documento/importar/progress/{id}', [DocumentoController::class, 'buscar_progress_batch'])->name('documento.importar.progress.buscar');
Route::get('/documento/importar/now/{id}', [DocumentoController::class, 'buscar_progress_now'])->name('documento.importar.progress.now');
Route::post('/documento/espaco-ocupado/{id}', [DocumentoController::class, 'espaco_ocupado'])->name('documento.editar.espaco_ocupado');
Route::patch('/documento/tipo-documento/{id}', [DocumentoController::class, 'alterar_tipo_documental'])->name('documento.editar.tipo_documental');


 // routes caixas
 Route::get('/caixa', [CaixaController::class, 'index'])->name('caixa.show');
 Route::get('/caixa/{id}', [CaixaController::class, 'show'])->name('caixa.detalhes');
 Route::put('/caixa/{id}', [CaixaController::class, 'update'])->name('caixa.update');
 Route::delete('/caixa/{id}', [CaixaController::class, 'destroy'])->name('caixa.destroy');
 Route::post('/caixa', [DocumentoController::class, 'criarNovaCaixa'])->name('caixa.criar');

 // routes para repactuação
 Route::put('/repactuar/fila/{id}', [RepactuacaoController::class, 'salvar_fila_repactuacao'])->name('repactuacao.salvar_fila_repactuacao');
 Route::get('/repactuar/fila', [RepactuacaoController::class, 'fila'])->name('repactuacao.fila');
 Route::post('/repactuar/enderecar', [RepactuacaoController::class, 'enderecar'])->name('repactuacao.enderecar');
 Route::get('/repactuar/lista', [RepactuacaoController::class, 'lista'])->name('repactuacao.lista');
 Route::put('/repactuar/fila/deletar/{id}', [RepactuacaoController::class, 'remover_fila_repactuacao'])->name('repactuacao.remover_fila_repactuacao');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');

// ruoutes predios
Route::get('/predios/disponiveis', [PredioController::class, 'disponiveis'])->name('predios.disponiveis');

//routes usuarios
Route::get('/usuario', [UserController::class, 'listar'])->name('usuario.listar');
Route::post('/usuario/perfil/{id}', [UserController::class, 'salvarPerfil'])->name('usuario.perfil');
Route::patch('/usuario/disable-login/{id}', [UserController::class, 'disableLogin'])->name('usuario.disable.login');

//routes roles
Route::get('/roles', [RoleController::class, 'listar'])->name('roles.listar');

//routes perfil
Route::get('/perfil', [PerfilController::class, 'listar'])->name('perfil.listar');
Route::get('/perfil/permissao/{id}', [PerfilController::class, 'listarPermissao'])->name('perfil.listar.permissao');
Route::post('/perfil', [PerfilController::class, 'criar'])->name('perfil.criar');
Route::post('/perfil/{id}', [PerfilController::class, 'permissao'])->name('perfil.permissao');


});

// rotas de login e tokens
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'register'])->name('register');

