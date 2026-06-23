<?php

use App\Http\Controllers\GmailController;
use App\Http\Controllers\GmailFilterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->middleware('auth')->name('home');

Route::get('/oauth/gmail/login', [GmailController::class, 'login'])->name('gmail.login');
Route::get('/oauth/gmail/callback', [GmailController::class, 'callback'])->name('gmail.callback');

Route::middleware(['auth', 'gmail.auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile/clear', [UserController::class, 'clearData'])->name('profile.clearData');

    Route::get('/gmails', [GmailController::class, 'mails'])->name('gmail.mails');
    Route::get('/gmail/{id}/pdf', [GmailController::class, 'downloadPdf'])->name('gmail.downloadPdf');
    Route::get('/gmail/{id}/body', [GmailController::class, 'mailBody'])->name('gmail.mailBody');
    Route::any('/ajax/gmail/load', [GmailController::class, 'ajaxLoad'])->name('gmail.ajaxLoad');
    Route::get('/ajax/gmail/load-status/{filterId}', [GmailController::class, 'loadStatus'])->name('gmail.loadStatus');
    Route::get('/gmail/{mailId}/attachment/{attachmentId}/download', [GmailController::class, 'downloadAttachment'])->name('gmail.downloadAttachment');
    Route::post('/gmail/{id}/delete', [GmailController::class, 'destroy'])->name('gmail.delete');
    Route::post('/gmails/checkbox-action', [GmailController::class, 'checkboxAction'])->name('gmail.checkboxAction');

    Route::get('/gmail/filters', [GmailFilterController::class, 'index'])->name('gmailFilter.index');
    Route::get('/gmail/filters/create', [GmailFilterController::class, 'create'])->name('gmailFilter.create');
    Route::post('/gmail/filters/store', [GmailFilterController::class, 'store'])->name('gmailFilter.store');
    Route::get('/gmail/filters/{id}/edit', [GmailFilterController::class, 'edit'])->name('gmailFilter.edit');
    Route::post('/gmail/filters/{id}/update', [GmailFilterController::class, 'update'])->name('gmailFilter.update');
    Route::post('/gmail/filters/{id}/delete', [GmailFilterController::class, 'destroy'])->name('gmailFilter.delete');

    Route::get('/oauth/gmail/disconnect', [GmailController::class, 'disconnect'])->name('gmail.disconnect');
});

require __DIR__.'/auth.php';
