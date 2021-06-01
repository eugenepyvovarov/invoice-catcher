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
Auth::routes();

Route::get('/', function () {
    return redirect('dashboard');
});

Route::get('agreement', [\App\Http\Controllers\Auth\RegisterController::class, 'agreement'])->name('agreement');


Route::group(['middleware' => ['auth']], function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/test', [\App\Http\Controllers\HomeController::class, 'test'])->name('test');

      // Gamil Profile
    Route::get('/gmail/profile', [\App\Http\Controllers\GmailController::class, 'profile'])->name('gmail.profile');
    Route::post('/gmail/profile/clear', [\App\Http\Controllers\GmailController::class, 'clearProfile'])->name('gmail.profile.clear');

    // User Profile
    Route::get('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('profile');
    Route::post('/profile/clear', [\App\Http\Controllers\UserController::class, 'clearData'])->name('profile.clearData');

    // Mails
    Route::get('/gmails', [\App\Http\Controllers\GmailController::class, 'mails'])->name('gmail.mails');
    Route::get('/gmail/{id}/pdf', [\App\Http\Controllers\GmailController::class, 'downloadPdf'])->name('gmail.downloadPdf');
    Route::get('/gmail/{id}/body', [\App\Http\Controllers\GmailController::class, 'mailBody'])->name('gmail.mailBody');
    Route::get('/gmail/load', [\App\Http\Controllers\GmailController::class, 'load'])->name('gmail.load');
    Route::get('/gmail/{mailId}/attachment/{attachmentId}/download', [\App\Http\Controllers\GmailController::class, 'downloadAttachment'])->name('gmail.downloadAttachment');
    Route::post('/gmails/checkbox-action', [\App\Http\Controllers\GmailController::class, 'checkboxAction'])->name('gmail.checkboxAction');

    // Filters
    Route::get('/gmail/filters', [\App\Http\Controllers\GmailFilterController::class, 'index'])->name('gmailFilter.index');
    Route::get('/gmail/filters/create', [\App\Http\Controllers\GmailFilterController::class, 'create'])->name('gmailFilter.create');
    Route::post('/gmail/filters/store', [\App\Http\Controllers\GmailFilterController::class, 'store'])->name('gmailFilter.store');
    Route::get('/gmail/filters/{id}/edit', [\App\Http\Controllers\GmailFilterController::class, 'edit'])->name('gmailFilter.edit');
    Route::post('/gmail/filters/{id}/update', [\App\Http\Controllers\GmailFilterController::class, 'update'])->name('gmailFilter.update');
    Route::post('/gmail/filters/{id}/delete', [\App\Http\Controllers\GmailFilterController::class, 'destroy'])->name('gmailFilter.delete');

    // Auth
    Route::get('/oauth/gmail/connect', [\App\Http\Controllers\GmailController::class, 'connect'])->name('gmail.connect');
    Route::get('/oauth/gmail/disconnect', [\App\Http\Controllers\GmailController::class, 'disconnect'])->name('gmail.disconnect');
    Route::get('/oauth/gmail/callback', [\App\Http\Controllers\GmailController::class, 'callback'])->name('gmail.callback');
});

require __DIR__.'/auth.php';

