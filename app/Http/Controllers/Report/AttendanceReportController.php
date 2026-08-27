<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    /**
     * Daily Attendance Report
     */
    public function daily(Request $request)
    {
        Gate::authorize('viewReports', Attendance::class);
        
        $date = $request->input('date', date('Y-m-d'));
        
        $classes = SchoolClass::forCurrentSchool()->get();
        if (Auth::user()->hasRole('Teacher') && Auth::user()->teacherProfile) {
            $classes = Auth::user()->teacherProfile->classes;
        }

        $query = Attendance::with(['student', 'schoolClass', 'section'])
            ->forCurrentSchool()
            ->where('date', $date);

        if ($request->filled('school_class_id')) {
            $query->where('school_class_id', $request->school_class_id);
        } elseif (Auth::user()->hasRole('Teacher') && Auth::user()->teacherProfile) {
            $query->whereIn('school_class_id', $classes->pluck('id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        $attendances = $query->get();

        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
        ];

        return view('reports.attendance.daily', compact('date', 'classes', 'attendances', 'summary'));
    }

    /**
     * Monthly Attendance Report
     */
    public function monthly(Request $request)
    {
        Gate::authorize('viewReports', Attendance::class);
        
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $classes = SchoolClass::forCurrentSchool()->get();
        if (Auth::user()->hasRole('Teacher') && Auth::user()->teacherProfile) {
            $classes = Auth::user()->teacherProfile->classes;
        }

        $reportData = collect();
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        
        if ($request->filled('school_class_id') && $request->filled('section_id')) {
            $students = Student::forCurrentSchool()
                ->whereHas('enrollments', function($q) use ($request) {
                    $q->where('school_class_id', $request->school_class_id)
                      ->where('section_id', $request->section_id)
                      ->where('status', 'active');
                })->get();

            $attendances = Attendance::forCurrentSchool()
                ->where('school_class_id', $request->school_class_id)
                ->where('section_id', $request->section_id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                $studentAtt = $attendances->get($student->id, collect());
                
                $days = [];
                $presentCount = 0;
                
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $record = $studentAtt->first(function ($att) use ($dateStr) {
                        return $att->date->format('Y-m-d') === $dateStr;
                    });
                    
                    if ($record) {
                        $days[$d] = $record->status;
                        if (in_array($record->status, ['present', 'late'])) {
                            $presentCount++;
                        } elseif ($record->status == 'half_day') {
                            $presentCount += 0.5;
                        }
                    } else {
                        $days[$d] = null;
                    }
                }
                
                $workingDays = $studentAtt->count() ?: 1; // avoid division by zero
                $percentage = $studentAtt->count() > 0 ? round(($presentCount / $studentAtt->count()) * 100, 2) : 0;

                $reportData->push([
                    'student' => $student,
                    'days' => $days,
                    'present' => $presentCount,
                    'total_marked' => $studentAtt->count(),
                    'percentage' => $percentage
                ]);
            }
        }

        return view('reports.attendance.monthly', compact('classes', 'month', 'year', 'daysInMonth', 'reportData'));
    }

    /**
     * Student Attendance Report
     */
    public function student(Request $request)
    {
        Gate::authorize('viewReports', Attendance::class);
        
        $classes = SchoolClass::forCurrentSchool()->get();
        if (Auth::user()->hasRole('Teacher') && Auth::user()->teacherProfile) {
            $classes = Auth::user()->teacherProfile->classes;
        }

        $student = null;
        $attendances = collect();
        $summary = [];

        if ($request->filled('student_id')) {
            $student = Student::forCurrentSchool()->findOrFail($request->student_id);
            
            $query = Attendance::where('student_id', $student->id);
            
            if ($request->filled('month')) {
                $query->whereMonth('date', $request->month);
            }
            if ($request->filled('year')) {
                $query->whereYear('date', $request->year);
            }
            
            $attendances = $query->orderBy('date', 'desc')->get();
            
            $summary = [
                'total' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
            ];
            
            $presentAndLate = $summary['present'] + $summary['late'] + ($summary['half_day'] * 0.5);
            $summary['percentage'] = $summary['total'] > 0 ? round(($presentAndLate / $summary['total']) * 100, 2) : 0;
        }

        return view('reports.attendance.student', compact('classes', 'student', 'attendances', 'summary'));
    }
}
