<?php

use Illuminate\Support\Facades\Route;

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
Auth::routes(['register' => false, 'login']);

Route::get('/', [\App\Http\Controllers\HomeController::class, 'home'])->name('home');

Route::get('/oauth/gmail/login', [\App\Http\Controllers\GmailController::class, 'login'])->name('gmail.login');
Route::get('/oauth/gmail/callback', [\App\Http\Controllers\GmailController::class, 'callback'])->name('gmail.callback');

Route::group(['middleware' => ['auth', 'gmail.auth']], function () {

    // User Profile
    Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('profile');
    Route::post('/profile/clear', [\App\Http\Controllers\UserController::class, 'clearData'])->name('profile.clearData');

    // Mails
    Route::get('/gmails', [\App\Http\Controllers\GmailController::class, 'mails'])->name('gmail.mails');
    Route::get('/gmail/{id}/pdf', [\App\Http\Controllers\GmailController::class, 'downloadPdf'])->name('gmail.downloadPdf');
    Route::get('/gmail/{id}/body', [\App\Http\Controllers\GmailController::class, 'mailBody'])->name('gmail.mailBody');
    Route::any('/ajax/gmail/load', [\App\Http\Controllers\GmailController::class, 'ajaxLoad'])->name('gmail.ajaxLoad');
    Route::get('/gmail/{mailId}/attachment/{attachmentId}/download', [\App\Http\Controllers\GmailController::class, 'downloadAttachment'])->name('gmail.downloadAttachment');
    Route::post('/gmail/{id}/delete', [\App\Http\Controllers\GmailController::class, 'destroy'])->name('gmail.delete');
    Route::post('/gmails/checkbox-action', [\App\Http\Controllers\GmailController::class, 'checkboxAction'])->name('gmail.checkboxAction');

    // Filters
    Route::get('/gmail/filters', [\App\Http\Controllers\GmailFilterController::class, 'index'])->name('gmailFilter.index');
    Route::get('/gmail/filters/create', [\App\Http\Controllers\GmailFilterController::class, 'create'])->name('gmailFilter.create');
    Route::post('/gmail/filters/store', [\App\Http\Controllers\GmailFilterController::class, 'store'])->name('gmailFilter.store');
    Route::get('/gmail/filters/{id}/edit', [\App\Http\Controllers\GmailFilterController::class, 'edit'])->name('gmailFilter.edit');
    Route::post('/gmail/filters/{id}/update', [\App\Http\Controllers\GmailFilterController::class, 'update'])->name('gmailFilter.update');
    Route::post('/gmail/filters/{id}/delete', [\App\Http\Controllers\GmailFilterController::class, 'destroy'])->name('gmailFilter.delete');

    // Auth
    Route::get('/oauth/gmail/disconnect', [\App\Http\Controllers\GmailController::class, 'disconnect'])->name('gmail.disconnect');
});

