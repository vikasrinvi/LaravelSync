<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/sync', [SyncController::class, 'index'])->name('sync.index');
    Route::get('/syncFolders', [SyncController::class, 'syncFolders'])->name('sync.syncFolders');
    
    Route::get('/syncDropBoxFolders', [SyncController::class, 'syncDropBoxFolders'])->name('sync.syncDropBoxFolders');
    Route::get('/syncGoogleFolders', [SyncController::class, 'syncGoogleFolders'])->name('sync.syncGoogleFolders');


    Route::get('/getDropboxTopFolder', [SyncController::class, 'getDropboxTopFolder'])->name('sync.getDropboxTopFolder');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
