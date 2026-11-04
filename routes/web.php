<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Commercial\BackupController;
use App\Http\Controllers\Commercial\CashRegisterController;
use App\Http\Controllers\Commercial\DashboardController;
use App\Http\Controllers\Commercial\InventoryController;
use App\Http\Controllers\Commercial\ModuleController;
use App\Http\Controllers\Commercial\PosController;
use App\Http\Controllers\Commercial\ProductionController;
use App\Http\Controllers\Commercial\PurchaseController;
use App\Http\Controllers\Commercial\ReportsController;
use App\Http\Controllers\Commercial\SettingsController;
use App\Http\Controllers\Commercial\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'loginForm'])->name('login');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/senha/recuperar', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/senha/recuperar', [AuthController::class, 'forgot'])->name('password.email');
    Route::get('/senha/resetar/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/senha/resetar', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/senha/alterar', [AuthController::class, 'changePasswordForm'])->name('password.change');
    Route::post('/senha/alterar', [AuthController::class, 'changePassword'])->name('password.change.store');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/pdv', [PosController::class, 'create'])->middleware('permission:sales.manage')->name('pos.create');
    Route::post('/pdv', [PosController::class, 'store'])->middleware('permission:sales.manage')->name('pos.store');
    Route::get('/pedidos/{id}/comprovante', [PosController::class, 'receipt'])->name('sales.receipt');
    Route::post('/pedidos/{id}/cancelar', [PosController::class, 'cancel'])->middleware('permission:sales.cancel')->name('sales.cancel');
    Route::get('/producao', [ProductionController::class, 'index'])->middleware('permission:sales.manage')->name('production.index');
    Route::post('/pedidos/{id}/status', [ProductionController::class, 'updateStatus'])->middleware('permission:sales.manage')->name('orders.status');
    Route::get('/pedidos/{id}/imprimir/{sector}', [ProductionController::class, 'printSector'])->middleware('permission:sales.manage')->name('orders.print');

    Route::get('/caixa', [CashRegisterController::class, 'index'])->middleware('permission:cash.manage')->name('cash.index');
    Route::post('/caixa/abrir', [CashRegisterController::class, 'open'])->middleware('permission:cash.open')->name('cash.open');
    Route::post('/caixa/movimento', [CashRegisterController::class, 'movement'])->middleware('permission:cash.manage')->name('cash.movement');
    Route::post('/caixa/{id}/fechar', [CashRegisterController::class, 'close'])->middleware('permission:cash.close')->name('cash.close');

    Route::get('/compras', [PurchaseController::class, 'index'])->middleware('permission:purchases.manage')->name('purchases.index');
    Route::post('/compras', [PurchaseController::class, 'store'])->middleware('permission:purchases.manage')->name('purchases.store');

    Route::get('/estoque', [StockController::class, 'movements'])->middleware('permission:stock.manage')->name('stock.movements');
    Route::post('/estoque/ajuste', [StockController::class, 'adjust'])->middleware('permission:stock.adjust')->name('stock.adjust');

    Route::get('/inventario', [InventoryController::class, 'index'])->middleware('permission:inventory.manage')->name('inventory.index');
    Route::post('/inventario', [InventoryController::class, 'open'])->middleware('permission:inventory.manage')->name('inventory.open');
    Route::get('/inventario/{id}', [InventoryController::class, 'show'])->middleware('permission:inventory.manage')->name('inventory.show');
    Route::post('/inventario/{id}/contagem', [InventoryController::class, 'count'])->middleware('permission:inventory.manage')->name('inventory.count');
    Route::post('/inventario/{id}/aplicar', [InventoryController::class, 'apply'])->middleware('permission:stock.adjust')->name('inventory.apply');

    Route::get('/relatorios', [ReportsController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/backups', [BackupController::class, 'index'])->middleware('permission:backup.manage')->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->middleware('permission:backup.manage')->name('backups.store');
    Route::get('/backups/{id}/download', [BackupController::class, 'download'])->middleware('permission:backup.manage')->name('backups.download');
    Route::get('/configuracoes', [SettingsController::class, 'edit'])->middleware('permission:settings.manage')->name('settings.edit');
    Route::post('/configuracoes', [SettingsController::class, 'update'])->middleware('permission:settings.manage')->name('settings.update');

    Route::prefix('/cadastros/{module}')->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/novo', [ModuleController::class, 'create'])->name('modules.create');
        Route::post('/', [ModuleController::class, 'store'])->name('modules.store');
        Route::get('/{id}/editar', [ModuleController::class, 'edit'])->name('modules.edit');
        Route::put('/{id}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('/{id}', [ModuleController::class, 'destroy'])->name('modules.destroy');
    });
});
