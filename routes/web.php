<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanelController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [PanelController::class, 'index'])->name('index');
Route::post('/sendMessage', [PanelController::class, 'sendMessage'])->name('sendMessage');
Route::post('/getCredit', [PanelController::class, 'getCredit'])->name('getCredit');
Route::post('/getStatus', [PanelController::class, 'getStatus'])->name('getStatus');
