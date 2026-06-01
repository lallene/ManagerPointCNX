<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\TypeDistributionController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\Motif_consultationController;
use App\Http\Controllers\MedocRapportController;

use App\Http\Controllers\HomeController;



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

Route::get('/agents', [AgentController::class, 'index'])->name('new');

