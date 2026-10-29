<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\RutaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::post('/empresas/{empresa}/entrar', [EmpresaController::class, 'entrar'])->name('empresas.entrar');
    Route::post('/empresas/salir', [EmpresaController::class, 'salir'])->name('empresas.salir');

    Route::middleware('empresa')->group(function () {
    Route::get('/licencia', [EmpresaController::class, 'licencia'])->name('licencia');
    Route::post('/licencia', [EmpresaController::class, 'activar'])->name('licencia.activar');

    Route::middleware('vigente')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/atrasados', [DashboardController::class, 'atrasados'])->name('atrasados');
    Route::get('/ruta', [RutaController::class, 'index'])->name('ruta');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');

    Route::get('/prestamos/crear', [PrestamoController::class, 'create'])->name('prestamos.create');
    Route::post('/prestamos', [PrestamoController::class, 'store'])->name('prestamos.store');
    Route::get('/prestamos/{prestamo}', [PrestamoController::class, 'show'])->name('prestamos.show');
    Route::post('/prestamos/{prestamo}/pagar', [PrestamoController::class, 'pagar'])->name('prestamos.pagar');
    Route::post('/prestamos/{prestamo}/detener', [PrestamoController::class, 'detener'])->name('prestamos.detener');
    Route::post('/prestamos/{prestamo}/reanudar', [PrestamoController::class, 'reanudar'])->name('prestamos.reanudar');
    Route::post('/prestamos/{prestamo}/anular', [PrestamoController::class, 'anular'])->name('prestamos.anular');
    Route::post('/cuotas/{cuota}/aplazar', [PrestamoController::class, 'aplazar'])->name('cuotas.aplazar');
    });
    });
});
