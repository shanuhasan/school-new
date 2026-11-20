<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // School Switcher
    Route::post('/school/switch', [\App\Http\Controllers\SchoolSwitcherController::class, 'switch'])->name('school.switch');

    // "Login as" (impersonation) — Super Admin only; see ImpersonationController.
    // stop() stays reachable while impersonating (the impersonated user isn't a Super Admin).
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserAdminController::class, 'index'])->name('admin.users.index');

    // Roles & permissions manager (Super Admin only)
    Route::get('/admin/roles', [\App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->name('admin.roles.index');
    Route::get('/admin/roles/{role}/edit', [\App\Http\Controllers\Admin\RolePermissionController::class, 'edit'])->name('admin.roles.edit');
    Route::put('/admin/roles/{role}', [\App\Http\Controllers\Admin\RolePermissionController::class, 'update'])->name('admin.roles.update');
    Route::post('/admin/impersonate/{user}', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('admin.impersonate.start');
    Route::post('/admin/impersonate-stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])->name('admin.impersonate.stop');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('academic')->name('academic.')->group(function () {
    Route::resource('sessions', \App\Http\Controllers\AcademicSessionController::class);
    Route::resource('classes', \App\Http\Controllers\SchoolClassController::class);
    Route::get('classes/{class}/sections', [\App\Http\Controllers\SchoolClassController::class, 'getSections'])->name('classes.sections');
    Route::resource('sections', \App\Http\Controllers\SectionController::class);
    Route::resource('subjects', \App\Http\Controllers\SubjectController::class);
    
    // Attendance
    Route::get('attendance', [\App\Http\Controllers\Academic\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/mark', [\App\Http\Controllers\Academic\AttendanceController::class, 'mark'])->name('attendance.mark');
    Route::post('attendance', [\App\Http\Controllers\Academic\AttendanceController::class, 'store'])->name('attendance.store');

    // Timetables
    Route::get('timetables', [\App\Http\Controllers\Academic\TimetableController::class, 'index'])->name('timetables.index');
    Route::get('timetables/manage', [\App\Http\Controllers\Academic\TimetableController::class, 'manage'])->name('timetables.manage');
    Route::post('timetables', [\App\Http\Controllers\Academic\TimetableController::class, 'store'])->name('timetables.store');
    Route::delete('timetables/{timetable}', [\App\Http\Controllers\Academic\TimetableController::class, 'destroy'])->name('timetables.destroy');
    
    // Homework
    Route::resource('homework', \App\Http\Controllers\Academic\HomeworkController::class);
    Route::get('homework/{homework}/download', [\App\Http\Controllers\Academic\HomeworkController::class, 'downloadAttachment'])->name('homework.download');
    
    // Homework Submissions
    Route::post('homework/{homework}/submissions', [\App\Http\Controllers\Academic\HomeworkSubmissionController::class, 'store'])->name('homework.submissions.store');
    Route::patch('submissions/{submission}/evaluate', [\App\Http\Controllers\Academic\HomeworkSubmissionController::class, 'evaluate'])->name('homework.submissions.evaluate');
    Route::get('submissions/{submission}/download', [\App\Http\Controllers\Academic\HomeworkSubmissionController::class, 'downloadAttachment'])->name('homework.submissions.download');
    
    // Examinations
    Route::resource('exam-types', \App\Http\Controllers\Academic\ExamTypeController::class)->except(['create', 'show', 'edit', 'update']);
    Route::resource('grades', \App\Http\Controllers\Academic\GradeController::class)->except(['create', 'show', 'edit', 'update']);
    Route::resource('exams', \App\Http\Controllers\Academic\ExamController::class);
    Route::post('exams/{exam}/subjects', [\App\Http\Controllers\Academic\ExamController::class, 'assignSubject'])->name('exams.subjects.assign');
    Route::delete('exam-subjects/{examSubject}', [\App\Http\Controllers\Academic\ExamController::class, 'destroySubject'])->name('exams.subjects.destroy');
    
    // Marks Entry
    Route::get('marks', [\App\Http\Controllers\Academic\MarksController::class, 'index'])->name('marks.index');
    Route::post('marks', [\App\Http\Controllers\Academic\MarksController::class, 'store'])->name('marks.store');
    
    // Results
    Route::get('results', [\App\Http\Controllers\Academic\ResultController::class, 'index'])->name('results.index');
    Route::post('results/process', [\App\Http\Controllers\Academic\ResultController::class, 'process'])->name('results.process');
    Route::post('results/publish', [\App\Http\Controllers\Academic\ResultController::class, 'publish'])->name('results.publish');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('reports')->name('reports.')->group(function () {
    Route::get('attendance/daily', [\App\Http\Controllers\Report\AttendanceReportController::class, 'daily'])->name('attendance.daily');
    Route::get('attendance/monthly', [\App\Http\Controllers\Report\AttendanceReportController::class, 'monthly'])->name('attendance.monthly');
    Route::get('attendance/student', [\App\Http\Controllers\Report\AttendanceReportController::class, 'student'])->name('attendance.student');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->prefix('staff')->name('staff.')->group(function () {
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except(['show']);
    Route::resource('designations', \App\Http\Controllers\DesignationController::class)->except(['show']);
    // Bulk import / export (before the resource so "import" isn't read as a teacher id)
    Route::get('teachers/import', [\App\Http\Controllers\TeacherImportController::class, 'create'])->name('teachers.import.create');
    Route::get('teachers/import/template', [\App\Http\Controllers\TeacherImportController::class, 'template'])->name('teachers.import.template');
    Route::post('teachers/import', [\App\Http\Controllers\TeacherImportController::class, 'store'])->name('teachers.import.store');
    Route::get('teachers/export', \App\Http\Controllers\TeacherExportController::class)->name('teachers.export');

    Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
    Route::resource('staffs', \App\Http\Controllers\StaffController::class);
    
    // Teacher Timetable
    Route::get('my-timetable', [\App\Http\Controllers\Staff\TeacherTimetableController::class, 'index'])->name('timetables.teacher');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTenantContext::class])->group(function () {
    // Bulk import (registered before the resource so "import" isn't read as a student id)
    Route::get('students/import', [\App\Http\Controllers\StudentImportController::class, 'create'])->name('students.import.create');
    Route::get('students/import/template', [\App\Http\Controllers\StudentImportController::class, 'template'])->name('students.import.template');
    Route::post('students/import', [\App\Http\Controllers\StudentImportController::class, 'store'])->name('students.import.store');
    Route::get('students/export', \App\Http\Controllers\StudentExportController::class)->name('students.export');

    Route::get('guardians/import', [\App\Http\Controllers\GuardianImportController::class, 'create'])->name('guardians.import.create');
    Route::get('guardians/import/template', [\App\Http\Controllers\GuardianImportController::class, 'template'])->name('guardians.import.template');
    Route::post('guardians/import', [\App\Http\Controllers\GuardianImportController::class, 'store'])->name('guardians.import.store');
    Route::get('guardians/export', \App\Http\Controllers\GuardianExportController::class)->name('guardians.export');

    Route::resource('students', \App\Http\Controllers\StudentController::class);
    Route::resource('guardians', \App\Http\Controllers\GuardianController::class);
    
    // Enrollments
    // Link / unlink an existing guardian to a student
    Route::post('students/{student}/guardians', [\App\Http\Controllers\StudentGuardianController::class, 'store'])->name('students.guardians.store');
    Route::delete('students/{student}/guardians/{guardian}', [\App\Http\Controllers\StudentGuardianController::class, 'destroy'])->name('students.guardians.destroy');

    Route::post('students/{student}/enrollments', [\App\Http\Controllers\EnrollmentController::class, 'store'])->name('students.enrollments.store');
    Route::put('students/{student}/enrollments/{enrollment}', [\App\Http\Controllers\EnrollmentController::class, 'update'])->name('students.enrollments.update');
    Route::delete('students/{student}/enrollments/{enrollment}', [\App\Http\Controllers\EnrollmentController::class, 'destroy'])->name('students.enrollments.destroy');

    // Documents
    Route::post('students/{student}/documents', [\App\Http\Controllers\StudentDocumentController::class, 'store'])->name('students.documents.store');
    Route::get('documents/{document}/download', [\App\Http\Controllers\StudentDocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [\App\Http\Controllers\StudentDocumentController::class, 'destroy'])->name('documents.destroy');
});

require __DIR__.'/auth.php';
