<?php

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\Api\V1\AuthController;

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

Route::middleware('auth:sanctum')->get('/users/{user}', function (\App\Models\User $user) {
    return ApiResponse::success($user);
});

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('leads', [\App\Http\Controllers\Api\V1\LeadController::class, 'index']);
});



Route::post('/webhook/leads', [TicketController::class, 'storeLead'])->name('webhook.leads');
//Route::post('/webhook/leads-test', [TicketController::class, 'storeLeadTest'])->name('webhook.leadstest');
Route::get('/testme', [TicketController::class, 'devTest'])->name('testme');
