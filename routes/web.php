<?php
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');
Route::post('/login', [PortalController::class, 'login'])->middleware('throttle:10,1');
Route::middleware('auth')->controller(PortalController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/students/create', 'createStudent')->name('students.create');
    Route::post('/students', 'storeStudent')->name('students.store');
    Route::post('/logout', 'logout');
    Route::post('/accounts/{account}/payments', 'pay')->whereNumber('account');
    Route::get('/receipts/{payment}/print', 'printReceipt')->whereNumber('payment')->name('receipts.print');
    Route::get('/receipts/{payment}/pdf', 'pdfReceipt')->whereNumber('payment')->name('receipts.pdf');
    Route::get('/receipts/{payment}', 'receipt')->whereNumber('payment');
});
