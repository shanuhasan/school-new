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

    // School Switcher
    Route::post('/school/switch', [\App\Http\Controllers\SchoolSwitcherController::class, 'switch'])->name('school.switch');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('academic')->name('academic.')->group(function () {
    Route::resource('sessions', \App\Http\Controllers\AcademicSessionController::class);
    Route::resource('classes', \App\Http\Controllers\SchoolClassController::class);
    Route::resource('sections', \App\Http\Controllers\SectionController::class);
    Route::resource('subjects', \App\Http\Controllers\SubjectController::class);
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('staff')->name('staff.')->group(function () {
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except(['show']);
    Route::resource('designations', \App\Http\Controllers\DesignationController::class)->except(['show']);
    Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
    Route::resource('staffs', \App\Http\Controllers\StaffController::class);
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->group(function () {
    Route::resource('students', \App\Http\Controllers\StudentController::class);
    Route::resource('guardians', \App\Http\Controllers\GuardianController::class);
    
    // Enrollments
    Route::post('students/{student}/enrollments', [\App\Http\Controllers\EnrollmentController::class, 'store'])->name('students.enrollments.store');
    Route::put('students/{student}/enrollments/{enrollment}', [\App\Http\Controllers\EnrollmentController::class, 'update'])->name('students.enrollments.update');
    Route::delete('students/{student}/enrollments/{enrollment}', [\App\Http\Controllers\EnrollmentController::class, 'destroy'])->name('students.enrollments.destroy');

    // Documents
    Route::post('students/{student}/documents', [\App\Http\Controllers\StudentDocumentController::class, 'store'])->name('students.documents.store');
    Route::get('documents/{document}/download', [\App\Http\Controllers\StudentDocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [\App\Http\Controllers\StudentDocumentController::class, 'destroy'])->name('documents.destroy');
});

require __DIR__.'/auth.php';
