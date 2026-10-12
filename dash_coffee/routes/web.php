<?php

use App\Http\Controllers\StaffAuthController;
use App\Http\Middleware\OwnerOnly;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Public pages (customers can see these)
|--------------------------------------------------------------------------
*/
Route::view('/', 'homepage')->name('home');
Route::view('/about', 'about')->name('about');
Route::view('/menu', 'menu')->name('menu');
Route::view('/contact', 'contact')->name('contact');

/*
|--------------------------------------------------------------------------
| Hidden staff login (no link on the public site)
|--------------------------------------------------------------------------
*/
Route::get('/staff/login', [StaffAuthController::class, 'showLogin'])->name('login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])
    ->middleware('throttle:5,1')   // max 5 tries per minute
    ->name('login.submit');

/*
|--------------------------------------------------------------------------
| Staff pages (must be logged in)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('staff')->group(function () {
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');

    Route::get('/orders', [OrderController::class, 'create'])->name('staff.orders');
    Route::post('/orders', [OrderController::class, 'store'])->name('staff.orders.store');
    Route::get('/orders/history', [OrderController::class, 'history'])->name('staff.history');
    Route::get('/orders/{order}', [OrderController::class, 'receipt'])->name('staff.receipt');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('staff.inventory');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('staff.inventory.store');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('staff.inventory.adjust');
    Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])->name('staff.inventory.update');


    // Owner only
    Route::middleware(OwnerOnly::class)->group(function () {
        Route::get('/menu', [MenuController::class, 'index'])->name('staff.menu');
        Route::post('/menu', [MenuController::class, 'store'])->name('staff.menu.store');
        Route::patch('/menu/{product}', [MenuController::class, 'status'])->name('staff.menu.status');
        Route::delete('/menu/{product}', [MenuController::class, 'destroy'])->name('staff.menu.destroy');

        Route::get('/sales', [ReportController::class, 'sales'])->name('staff.sales');
        Route::get('/sales/export', [ReportController::class, 'export'])->name('staff.sales.export');

        Route::get('/reports/inventory', [InventoryReportController::class, 'index'])->name('staff.inv-reports');
        Route::get('/reports/inventory/xlsx', [InventoryReportController::class, 'xlsx'])->name('staff.inv-reports.xlsx');
        Route::get('/reports/inventory/pdf', [InventoryReportController::class, 'pdf'])->name('staff.inv-reports.pdf');

        Route::get('/accounts', fn () => view('staff.accounts', [
            'users' => \App\Models\User::orderBy('id')->get(),
        ]))->name('staff.accounts');
    });
});