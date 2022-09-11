<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\SalesCampaignController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Mail\LeadNotifyMail;

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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::match(['get', 'post'], '/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::match(['get', 'post'], '/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('users.all');
    Route::get('create', [UserController::class, 'create'])->name('users.create');
    Route::get('edit/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::get('banPermit/{id}', [UserController::class, 'toggleBan'])->name('users.banPermit');
    Route::delete('deleteRestore/{id}', [UserController::class, 'destroyRestore'])->name('users.deleteRestore');
    Route::post('store', [UserController::class, 'store'])->name('users.store');
    Route::put('update/{id}', [UserController::class, 'update'])->name('users.update');
//    Route::get('vehicles/{userId}', 'UserController@getVehicles')->name('user.vehicles');
});

Route::prefix('tickets')->group(function () {
    Route::match(['get', 'post'], '/all/{status?}', [TicketController::class, 'index'])->name('tickets.all');
    Route::get('show/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('store', [TicketController::class, 'store'])->name('tickets.store');
    Route::put('update/{id}', [TicketController::class, 'update'])->name('tickets.update');
    Route::get('edit/{id}', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::delete('delete/{id}', [TicketController::class, 'destroy'])->name('tickets.delete');
    Route::post('importFromExcel', [TicketController::class, 'importFromExcelFile'])->name('tickets.excel');
    Route::get('showExcel', [TicketController::class, 'showExcel'])->name('tickets.excelShow');
    Route::get('facebookLeads', [TicketController::class, 'showImportFromFacebookLead'])->name('tickets.facebook');
    Route::get('importFacebookLead', [TicketController::class, 'importFromFacebookLead'])->name('tickets.doFacebook');
    Route::post('moveForward/{id}', [TicketController::class, 'moveForward'])->name('tickets.moveForward');
    Route::post('makeInvoice/{id}', [TicketController::class, 'makeInvoice'])->name('tickets.makeInvoice');
    Route::post('attachPassport/{id}', [TicketController::class, 'attachPassport'])->name('tickets.attachPassport');
    //Route::get('reviewed', [TicketController::class, 'indexReviewed'])->name('tickets.reviewed');
    Route::get('download/{type}/{id}', [TicketController::class, 'downloadAttachment'])->name('tickets.download');
    Route::get('archived', [TicketController::class, 'indexArchived'])->name('tickets.archived');
    Route::post('multipleForward', [TicketController::class, 'multipleForward'])->name('tickets.multipleForward');
//    Route::match(['get', 'post'], '/report', [TicketController::class, 'getReport'])->name('tickets.report');

//    Route::get('vehicles/{userId}', 'UserController@getVehicles')->name('user.vehicles');
});
Route::get('/ticket/pdf', [TicketController::class, 'createPDF']);
Route::get('/ttt', function () {
    return view('ticketPDF');
});
Auth::routes();

Route::get('/email', function (){
    return new LeadNotifyMail();
})->name('tickets.email');

Route::prefix('salesCamps')->group(function () {
    Route::get('index', [SalesCampaignController::class, 'index'])->name('salesCamps.index');
    Route::delete('delete/{id}', [SalesCampaignController::class, 'destroy'])->name('salesCamps.delete');
    Route::post('store', [SalesCampaignController::class, 'store'])->name('salesCamps.store');
});

Route::prefix('settings')->group(function () {
    Route::put('update', [GeneralSettingsController::class, 'update'])->name('settings.update');
});

Route::get('devTest', [TicketController::class, 'devTest'])->name('devTest');


