<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'employee_id' => fake()->unique()->numerify('TCH-####'),
            'joining_date' => fake()->date(),
            'qualification' => 'Master of Education',
            'status' => 'active',
        ];
    }
}
