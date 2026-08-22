<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Guardian;
use Faker\Factory as Faker;
use App\Facades\Tenant;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // 1. Get or create a school
        $school = School::first();
        if (!$school) {
            $school = School::create([
                'name' => 'Springfield High School',
                'code' => 'SHS',
                'slug' => 'springfield-high',
                'email' => 'admin@springfield.edu',
                'phone' => '555-1234',
                'address' => '123 School Lane, Springfield',
                'status' => 'active',
            ]);
        }

        // Set the tenant context globally for the seeder
        Tenant::setSchool($school);

        // 2. Get or create an academic session
        $session = AcademicSession::where('school_id', $school->id)->where('is_current', true)->first();
        if (!$session) {
            $session = AcademicSession::create([
                'school_id' => $school->id,
                'name' => '2026-2027',
                'start_date' => '2026-08-01',
                'end_date' => '2027-05-31',
                'is_current' => true,
            ]);
        }

        // 3. Create Classes
        $classes = [];
        foreach (['Grade 9', 'Grade 10', 'Grade 11'] as $className) {
            $classes[] = SchoolClass::firstOrCreate(
                ['school_id' => $school->id, 'name' => $className]
            );
        }

        // 4. Create Sections for each class
        $sections = [];
        foreach ($classes as $class) {
            foreach (['Section A', 'Section B'] as $sectionName) {
                $sections[] = Section::firstOrCreate(
                    ['school_class_id' => $class->id, 'name' => $sectionName]
                );
            }
        }

        // 5. Create Subjects
        foreach (['Mathematics', 'Physics', 'Chemistry', 'English', 'History'] as $subjectName) {
            Subject::firstOrCreate([
                'school_id' => $school->id,
                'name' => $subjectName,
                'code' => strtoupper(substr($subjectName, 0, 3)),
            ]);
        }

        // 6. Create Guardians
        $guardians = [];
        for ($i = 0; $i < 10; $i++) {
            $guardians[] = Guardian::create([
                'school_id' => $school->id,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'occupation' => $faker->jobTitle,
                'address' => $faker->address,
            ]);
        }

        // 7. Create Students and Enroll them
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        
        for ($i = 0; $i < 30; $i++) {
            $student = Student::create([
                'school_id' => $school->id,
                'admission_number' => 'ADM-' . str_pad($i + 1000, 4, '0', STR_PAD_LEFT),
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'date_of_birth' => $faker->dateTimeBetween('-15 years', '-10 years')->format('Y-m-d'),
                'gender' => $faker->randomElement(['male', 'female']),
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'address' => $faker->address,
                'blood_group' => $faker->randomElement($bloodGroups),
                'admission_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'status' => 'active',
            ]);

            // Attach 1 or 2 random guardians
            $studentGuardians = $faker->randomElements($guardians, $faker->numberBetween(1, 2));
            foreach ($studentGuardians as $index => $guardian) {
                $student->guardians()->attach($guardian->id, [
                    'relationship' => $faker->randomElement(['Father', 'Mother', 'Guardian']),
                    'is_emergency_contact' => $index === 0, // Make the first one an emergency contact
                ]);
            }

            // Enroll the student
            $schoolClass = $faker->randomElement($classes);
            $classSections = array_filter($sections, fn($s) => $s->school_class_id === $schoolClass->id);
            $section = $faker->randomElement($classSections);

            $student->enrollments()->create([
                'school_id' => $school->id,
                'academic_session_id' => $session->id,
                'school_class_id' => $schoolClass->id,
                'section_id' => $section->id,
                'roll_number' => 'R-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);
        }

        $this->command->info('Dummy data seeded successfully! 30 Students, 10 Guardians, 3 Classes, 2 Sections, 5 Subjects created.');
    }
}
