<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DummyUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        // Ensure we have at least one school for tenant contexts
        $school = School::firstOrCreate(
            ['code' => 'DEMO-01'],
            [
                'name' => 'Demo International School',
                'slug' => 'demo-international-school',
                'address' => '123 Education Lane, Learning City',
                'email' => 'contact@demoschool.com',
                'phone' => '1234567890',
                'is_active' => true,
            ]
        );

        $roles = [
            'Super Admin',
            'School Admin',
            'Principal',
            'Teacher',
            'Accountant',
            'Librarian',
            'Transport Manager',
            'Staff',
            'Student',
            'Parent',
        ];

        foreach ($roles as $roleName) {
            // Make sure the role exists
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Create a predictable email like teacher@example.com
            $emailPrefix = Str::slug($roleName, '');
            $email = $emailPrefix . '@example.com';

            // Create the user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Demo ' . $roleName,
                    'phone' => '98765432' . rand(10, 99),
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            // Assign role
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // Assign to school and set tenant context (except for Super Admin, though they can have it too)
            if ($roleName !== 'Super Admin') {
                $user->update(['current_school_id' => $school->id]);
                if (!$user->schools()->where('school_id', $school->id)->exists()) {
                    $user->schools()->attach($school->id);
                }
            }
        }
    }
}
