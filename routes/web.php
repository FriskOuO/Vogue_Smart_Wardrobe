<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ClosetController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
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

    Route::get('/smart-closet', [ClosetController::class, 'hub'])->name('closet.hub');
    Route::get('/closet', [ClosetController::class, 'index'])->name('closet.index');
    Route::get('/closet/create', [ClosetController::class, 'create'])->name('closet.create');
    Route::get('/closet/ai-search', [ClosetController::class, 'search'])->name('closet.search');
    Route::get('/closet/stylist', [ClosetController::class, 'stylist'])->name('closet.stylist');
    Route::get('/closet/try-on', [ClosetController::class, 'tryOn'])->name('closet.tryon');
    Route::post('/closet', [ClosetController::class, 'store'])->name('closet.store');
    Route::get('/closet/{id}', [ClosetController::class, 'show'])->name('closet.show');

    Route::get('/workspace/{module}', [WorkspaceController::class, 'show'])->name('workspace.show');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserManagementController::class);
});

require __DIR__.'/auth.php';
