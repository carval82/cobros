<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\RegistroEmpresaController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

$cartera = function () {
    Route::get('/atrasados', [DashboardController::class, 'atrasados'])->name('atrasados');
    Route::get('/ruta', [RutaController::class, 'index'])->name('ruta');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [ClienteController::class, 'store'])->middleware('puede:clientes.escribir')->name('clientes.store');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->middleware('puede:clientes.escribir')->name('clientes.update');

    Route::get('/prestamos/crear', [PrestamoController::class, 'create'])->name('prestamos.create');
    Route::post('/prestamos', [PrestamoController::class, 'store'])->middleware('puede:prestamos.crear')->name('prestamos.store');
    Route::get('/prestamos/{prestamo}', [PrestamoController::class, 'show'])->name('prestamos.show');
    Route::post('/prestamos/{prestamo}/pagar', [PrestamoController::class, 'pagar'])->middleware('puede:cobros.registrar')->name('prestamos.pagar');
    Route::post('/prestamos/{prestamo}/detener', [PrestamoController::class, 'detener'])->middleware('puede:cobros.registrar')->name('prestamos.detener');
    Route::post('/prestamos/{prestamo}/reanudar', [PrestamoController::class, 'reanudar'])->middleware('puede:cobros.registrar')->name('prestamos.reanudar');
    Route::post('/prestamos/{prestamo}/anular', [PrestamoController::class, 'anular'])->middleware('puede:prestamos.anular')->name('prestamos.anular');
    Route::post('/prestamos/{prestamo}/refinanciar', [PrestamoController::class, 'refinanciar'])->middleware('puede:prestamos.refinanciar')->name('prestamos.refinanciar');
    Route::post('/cuotas/{cuota}/aplazar', [PrestamoController::class, 'aplazar'])->middleware('puede:cobros.registrar')->name('cuotas.aplazar');

    Route::middleware('puede:usuarios.gestionar')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/datos', [UsuarioController::class, 'datos'])->name('usuarios.datos');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    });
};

Route::get('/', [RegistroEmpresaController::class, 'portada'])->name('dashboard');
Route::post('/registrar', [RegistroEmpresaController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('registrar.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () use ($cartera) {
    Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::post('/empresas/{empresa}/entrar', [EmpresaController::class, 'entrar'])->name('empresas.entrar');
    Route::post('/empresas/salir', [EmpresaController::class, 'salir'])->name('empresas.salir');
    Route::get('/acerca', [EmpresaController::class, 'acerca'])->name('acerca');
    Route::get('/acerca/datos', [EmpresaController::class, 'acercaDatos'])->name('acerca.datos');
    Route::post('/acerca', [EmpresaController::class, 'activar'])->name('acerca.activar');
    Route::get('/sesion/datos', [UsuarioController::class, 'sesion'])->name('sesion.datos');

    Route::middleware('empresa')->group(function () use ($cartera) {
        Route::get('/licencia', [EmpresaController::class, 'licencia'])->name('licencia');
        Route::post('/licencia', [EmpresaController::class, 'activar'])->name('licencia.activar');

        Route::middleware('vigente')->group($cartera);
    });
});

Route::prefix('tenant/{tenant}')
    ->where(['tenant' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}'])
    ->name('tenant.')
    ->group(function () use ($cartera) {
        Route::middleware('guest')->group(function () {
            Route::get('/login', [LoginController::class, 'create'])->name('login');
            Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
        });

        Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

        Route::middleware(['auth', 'empresa'])->group(function () use ($cartera) {
            Route::get('/acerca', [EmpresaController::class, 'acerca'])->name('acerca');
            Route::get('/acerca/datos', [EmpresaController::class, 'acercaDatos'])->name('acerca.datos');
            Route::post('/acerca', [EmpresaController::class, 'activar'])->name('acerca.activar');
            Route::get('/sesion/datos', [UsuarioController::class, 'sesion'])->name('sesion.datos');
            Route::get('/licencia', [EmpresaController::class, 'licencia'])->name('licencia');
            Route::post('/licencia', [EmpresaController::class, 'activar'])->name('licencia.activar');

            Route::middleware('vigente')->group(function () use ($cartera) {
                Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
                $cartera();
            });
        });
    });
