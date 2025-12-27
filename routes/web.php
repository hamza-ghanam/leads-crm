<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\SalesCampaignController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Mail\LeadNotifyMail;
use App\Http\Controllers\WebNotificationController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\NotificationController;

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


Auth::routes();

Route::match(['get', 'post'], '/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::match(['get', 'post'], '/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home2');

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
    Route::get('showImports/{source}', [TicketController::class, 'showImportLeads'])->name('tickets.showImports');
    //Route::get('importLeads/{source}', [TicketController::class, 'importLeadsFromZapier'])->name('tickets.doImport');
   // Route::post('importLeads/{source}', [TicketController::class, 'importLeadsFromZapierV2'])->name('tickets.doImport');
    Route::post('importLeads/{source}', [TicketController::class, 'importLeadsFromZapierV3'])->name('tickets.doImport');
    Route::put('ignoreLeads/{type}', [TicketController::class, 'ignoreLeads'])->name('tickets.ignoreLeads');
    Route::post('moveForward/{id}', [TicketController::class, 'moveForward'])->name('tickets.moveForward');
    Route::post('makeInvoice/{id}', [TicketController::class, 'makeInvoice'])->name('tickets.makeInvoice');
    Route::post('attachPassport/{id}', [TicketController::class, 'attachPassport'])->name('tickets.attachPassport');
    //Route::get('reviewed', [TicketController::class, 'indexReviewed'])->name('tickets.reviewed');
    Route::get('download/{type}/{id}', [TicketController::class, 'downloadAttachment'])->name('tickets.download');
    Route::get('archived', [TicketController::class, 'indexArchived'])->name('tickets.archived');
    Route::post('multipleForward', [TicketController::class, 'multipleForward'])->name('tickets.multipleForward');
    Route::post('restoreLeads', [TicketController::class, 'restoreArchivedLeads'])->name('tickets.restore');
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


Route::post('/fcm/token', [FcmController::class, 'store'])->name('update.token')->middleware('auth');
Route::post('/send-notification',[WebNotificationController::class,'notification'])->name('send.notification');
Route::get('/notify',[WebNotificationController::class,'sendNotification'])->name('notify');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])
        ->name('notifications.show');
});

use App\Http\Controllers\StatusController;

Route::middleware(['auth', 'role:super-admin'])->group(function () {

    // Show status duration settings page
    Route::get('/statuses/durations', [StatusController::class, 'index'])
        ->name('statuses.durations');

    // Save status duration settings
    Route::post('/statuses/durations', [StatusController::class, 'saveDurations'])
        ->name('statuses.save-durations');

});

Route::get('/test', function () {
    return view('test');
});

