<?php

use App\Http\Controllers\PesananLaundryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pesanan-laundry');

Route::get('/pesanan-laundry', [PesananLaundryController::class, 'index'])->name('pesanan-laundry.index');
Route::post('/pesanan-laundry', [PesananLaundryController::class, 'store'])->name('pesanan-laundry.store');
Route::get('/pesanan-laundry/{id}/edit', [PesananLaundryController::class, 'edit'])
    ->whereNumber('id')->name('pesanan-laundry.edit');
Route::put('/pesanan-laundry/{id}', [PesananLaundryController::class, 'update'])
    ->whereNumber('id')->name('pesanan-laundry.update');
Route::delete('/pesanan-laundry/{id}', [PesananLaundryController::class, 'destroy'])
    ->whereNumber('id')->name('pesanan-laundry.destroy');
