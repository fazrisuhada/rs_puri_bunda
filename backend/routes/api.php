<?php

use App\Http\Controllers\AntrianController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/dashboard-monitoring', [DashboardController::class, 'index']);
Route::post('/antrian', [AntrianController::class, 'store']);

Route::middleware('auth:sanctum')->prefix('antrian')->group(function () {
    Route::get('/', [AntrianController::class, 'getAntrianPasien']);
    Route::post('/panggil', [AntrianController::class, 'panggilPasien']);
    Route::post('/panggil-selanjutnya', [AntrianController::class, 'panggilPasienSelanjutnya']);

    Route::put('{antrian_id}/selesai-dipanggil', [AntrianController::class, 'selesaiDipanggil']);
});
