<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/features/{feature}', [FeatureController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('features.show');

Route::middleware('auth')->group(function () {
    Route::get('/account', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserManagementController::class);
});

require __DIR__.'/auth.php';
