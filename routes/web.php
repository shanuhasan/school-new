<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('academic')->name('academic.')->group(function () {
    Route::resource('sessions', \App\Http\Controllers\AcademicSessionController::class);
    Route::resource('classes', \App\Http\Controllers\SchoolClassController::class);
    Route::resource('sections', \App\Http\Controllers\SectionController::class);
    Route::resource('subjects', \App\Http\Controllers\SubjectController::class);
});

require __DIR__.'/auth.php';
