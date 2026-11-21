<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            'students.view', 'students.create', 'students.update', 'students.delete',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.manage', 'attendance.view_reports',
            'timetable.view', 'timetable.manage',
            'homework.view', 'homework.manage', 'homework.submit',
            'fees.view', 'fees.create', 'fees.update', 'fees.collect', 'fees.refund',
            'salary.view', 'salary.manage',
            'exams.view', 'exams.create', 'exams.update', 'marks.enter', 'results.publish',
            'academic.manage',
            'staff.manage',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $schoolAdmin = Role::firstOrCreate(['name' => 'School Admin']);
        $principal = Role::firstOrCreate(['name' => 'Principal']);
        $teacher = Role::firstOrCreate(['name' => 'Teacher']);
        $accountant = Role::firstOrCreate(['name' => 'Accountant']);
        $librarian = Role::firstOrCreate(['name' => 'Librarian']);
        $transportManager = Role::firstOrCreate(['name' => 'Transport Manager']);
        $staff = Role::firstOrCreate(['name' => 'Staff']);
        $student = Role::firstOrCreate(['name' => 'Student']);
        $parent = Role::firstOrCreate(['name' => 'Parent']);

        // Assign some basic permissions to specific roles
        // Teacher
        $teacher->syncPermissions([
            'students.view',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.view_reports',
            'timetable.view',
            'homework.view', 'homework.manage',
            'exams.view', 'marks.enter'
        ]);

        // Accountant
        $accountant->syncPermissions([
            'fees.view', 'fees.create', 'fees.update', 'fees.collect', 'fees.refund',
            'salary.view', 'salary.manage',
        ]);

        // Principal / School Admin gets all for their school (simplified for now to all)
        $schoolAdmin->syncPermissions(Permission::all());
        $principal->syncPermissions(Permission::all());

        // Student / Parent — one sync each with the full intended set (don't call syncPermissions
        // twice for the same role: the second call replaces the first).
        // Note: fees are a staff tool (fees.view is management access), so students/parents
        // don't get it — a student-facing "my fees" view would be a separate permission.
        $student->syncPermissions([
            'attendance.view', 'timetable.view', 'exams.view',
            'homework.view', 'homework.submit',
        ]);
        $parent->syncPermissions([
            'students.view', 'attendance.view', 'timetable.view', 'exams.view',
            'homework.view',
        ]);

        // Super Admin gets all implicitly via Gate::before in AppServiceProvider
    }
}
