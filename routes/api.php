<?php

use App\Http\Controllers\MetaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/webhook/leads', [TicketController::class, 'storeLead'])->name('webhook.leads');
//Route::post('/webhook/leads-test', [TicketController::class, 'storeLeadTest'])->name('webhook.leadstest');
Route::get('/testme', [TicketController::class, 'devTest'])->name('testme');

Route::match(['GET', 'POST'], '/webhooks/meta', MetaController::class);
