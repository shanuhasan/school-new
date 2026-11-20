<?php

namespace Tests\Feature;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSubjectShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private School $school;
    private SchoolClass $class;
    private Subject $maths;
    private Subject $art;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->school = School::factory()->create();
        Tenant::setSchool($this->school);
        $this->user = User::factory()->create(['current_school_id' => $this->school->id]);
        $this->user->schools()->attach($this->school);
        $this->user->givePermissionTo('academic.manage');

        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Grade 5', 'level' => 5]);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $this->art = Subject::create(['school_id' => $this->school->id, 'name' => 'Art', 'code' => 'ART']);
        $this->class->subjects()->attach($this->maths->id, ['type' => 'compulsory']);
        $this->class->subjects()->attach($this->art->id, ['type' => 'optional']);
    }

    private function enrol(string $adm, string $first, ?Section $section, AcademicSession $session, string $roll): Student
    {
        $s = Student::create(['school_id' => $this->school->id, 'admission_number' => $adm, 'first_name' => $first, 'last_name' => 'Rao',
            'date_of_birth' => '2015-01-01', 'gender' => 'female', 'admission_date' => '2026-04-01', 'status' => 'active']);
        $s->enrollments()->create(['school_id' => $this->school->id, 'academic_session_id' => $session->id, 'school_class_id' => $this->class->id,
            'section_id' => $section?->id, 'roll_number' => $roll, 'status' => 'active']);

        return $s;
    }

    public function test_class_page_shows_sections_subjects_teachers_and_current_students()
    {
        $old = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2025-26', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31']);
        $current = AcademicSession::create(['school_id' => $this->school->id, 'name' => '2026-27', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]);
        $a = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);
        Section::create(['school_class_id' => $this->class->id, 'name' => 'B']);

        $riya = $this->enrol('ADM-1', 'Riya', $a, $current, '2');
        $this->enrol('ADM-2', 'Kabir', $a, $current, '10');
        $this->enrol('ADM-OLD', 'Graduated', $a, $old, '1'); // previous session: hidden

        $teacher = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => User::factory()->create(['name' => 'Anita Kumar'])->id]);
        $teacher->classes()->attach($this->class->id);
        $teacher->subjects()->attach($this->maths->id);

        $response = $this->actingAs($this->user)->get(route('academic.classes.show', $this->class));

        $response->assertOk()
            ->assertSee('Grade 5')
            ->assertSee('Showing students for 2026-27')
            ->assertSee('Section A')->assertSee('Section B')
            ->assertSee('Mathematics')->assertSee('(optional)', false)
            ->assertSee('Anita Kumar')
            ->assertSee(route('students.show', $riya), false)
            ->assertSee(route('staff.teachers.show', $teacher), false)
            ->assertSee(route('academic.subjects.show', $this->maths), false)
            ->assertSeeInOrder(['Riya', 'Kabir'])          // roll 2 before roll 10 (natural order)
            ->assertDontSee('Graduated')
            ->assertSee('school_class_id=' . $this->class->id . '&amp;section_id=' . $a->id, false); // attendance/timetable shortcuts
    }

    public function test_class_page_without_session_or_students()
    {
        $this->actingAs($this->user)->get(route('academic.classes.show', $this->class))
            ->assertOk()
            ->assertSee('No academic session yet')
            ->assertSee('No sections yet');
    }

    public function test_subject_page_shows_classes_teachers_and_homework()
    {
        $teacher = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => User::factory()->create(['name' => 'Anita Kumar'])->id]);
        $teacher->subjects()->attach($this->maths->id);
        $teacher->classes()->attach($this->class->id);

        $this->actingAs($this->user)->get(route('academic.subjects.show', $this->maths))
            ->assertOk()
            ->assertSee('Mathematics')->assertSee('MATH')
            ->assertSee('Grade 5')->assertSee('Compulsory')
            ->assertSee('Anita Kumar')->assertSee('Classes: Grade 5')
            ->assertSee(route('academic.classes.show', $this->class), false)
            ->assertSee('No homework set for this subject yet');
    }

    public function test_index_pages_link_to_show_pages()
    {
        $this->actingAs($this->user)->get(route('academic.classes.index'))->assertSee(route('academic.classes.show', $this->class), false);
        $this->actingAs($this->user)->get(route('academic.subjects.index'))->assertSee(route('academic.subjects.show', $this->maths), false);
    }

    public function test_class_edit_assigns_subjects_with_types()
    {
        $science = Subject::create(['school_id' => $this->school->id, 'name' => 'Science', 'code' => 'SCI']);

        $this->actingAs($this->user)->get(route('academic.classes.edit', $this->class))
            ->assertOk()->assertSee('Science')->assertSee('name="subjects[]"', false);

        // Keep Maths (compulsory), drop Art, add Science as optional
        $this->actingAs($this->user)->put(route('academic.classes.update', $this->class), [
            'name' => 'Grade 5', 'level' => 5, 'subjects_present' => 1,
            'subjects' => [$this->maths->id, $science->id],
            'optional' => [$science->id],
        ])->assertRedirect(route('academic.classes.show', $this->class));

        $types = $this->class->subjects()->get()->pluck('pivot.type', 'name')->all();
        ksort($types);
        $this->assertSame(['Mathematics' => 'compulsory', 'Science' => 'optional'], $types);

        // Unticking everything removes all subjects
        $this->actingAs($this->user)->put(route('academic.classes.update', $this->class), ['name' => 'Grade 5', 'subjects_present' => 1]);
        $this->assertSame(0, $this->class->subjects()->count());
    }

    public function test_class_edit_rejects_other_school_subject_and_keeps_subjects_when_section_absent()
    {
        $foreign = Subject::withoutGlobalScopes()->create(['school_id' => School::factory()->create()->id, 'name' => 'Foreign', 'code' => 'F']);

        $this->actingAs($this->user)->put(route('academic.classes.update', $this->class), [
            'name' => 'Grade 5', 'subjects_present' => 1, 'subjects' => [$foreign->id],
        ])->assertSessionHasErrors('subjects.0');
        $this->assertSame(2, $this->class->subjects()->count());

        // A rename without the subjects section (e.g. an API client) leaves subjects untouched
        $this->actingAs($this->user)->put(route('academic.classes.update', $this->class), ['name' => 'Grade 5A']);
        $this->assertSame(2, $this->class->subjects()->count());
        $this->assertSame('Grade 5A', $this->class->fresh()->name);
    }

    public function test_permission_and_school_isolation()
    {
        $plain = User::factory()->create(['current_school_id' => $this->school->id]);
        $plain->schools()->attach($this->school);
        $this->actingAs($plain)->get(route('academic.classes.show', $this->class))->assertForbidden();
        $this->actingAs($plain)->get(route('academic.subjects.show', $this->maths))->assertForbidden();

        $other = School::factory()->create();
        $foreignClass = SchoolClass::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'Foreign']);
        $foreignSubject = Subject::withoutGlobalScopes()->create(['school_id' => $other->id, 'name' => 'Foreign', 'code' => 'F']);
        $this->assertContains($this->actingAs($this->user)->get(route('academic.classes.show', $foreignClass))->status(), [403, 404]);
        $this->assertContains($this->actingAs($this->user)->get(route('academic.subjects.show', $foreignSubject))->status(), [403, 404]);
    }
}
