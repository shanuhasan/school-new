<?php

namespace App\Actions;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Creates a login account (User) for a Student or Guardian and links it via user_id.
 *
 * The account gets the matching role (Student / Parent), is attached to the person's school,
 * and is email-verified so they can sign in straight away. If no password is given, a random
 * one is generated and returned so the admin can hand it over (mail is log-only here).
 */
class CreateLoginAccount
{
    /** @return array{user: User, password: ?string, existed: bool} */
    public function forStudent(Student $student, ?string $password = null): array
    {
        return $this->make(
            person: $student,
            name: $student->full_name,
            email: $student->email,
            phone: $student->phone,
            role: 'Student',
            password: $password,
        );
    }

    /** @return array{user: User, password: ?string, existed: bool} */
    public function forGuardian(Guardian $guardian, ?string $password = null): array
    {
        return $this->make(
            person: $guardian,
            name: $guardian->full_name,
            email: $guardian->email,
            phone: $guardian->phone,
            role: 'Parent',
            password: $password,
        );
    }

    /**
     * @param  Student|Guardian  $person
     * @return array{user: User, password: ?string, existed: bool}
     */
    private function make($person, string $name, ?string $email, ?string $phone, string $role, ?string $password): array
    {
        // Already linked: don't create a second account.
        if ($person->user_id && ($existing = User::find($person->user_id))) {
            return ['user' => $existing, 'password' => null, 'existed' => true];
        }

        $email = trim((string) $email);
        if ($email === '') {
            throw new RuntimeException('An email address is required to create a login account.');
        }
        if (User::where('email', $email)->exists()) {
            throw new RuntimeException("The email {$email} is already used by another account.");
        }

        $plain = $password ?: Str::password(10, symbols: false);

        return DB::transaction(function () use ($person, $name, $email, $phone, $role, $plain) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($plain),
                'is_active' => true,
                'current_school_id' => $person->school_id,
            ]);

            // email_verified_at isn't mass-assignable; set it so they skip the verification wall.
            $user->markEmailAsVerified();

            $user->assignRole(Role::findOrCreate($role, 'web'));
            $user->schools()->syncWithoutDetaching([$person->school_id]);

            $person->forceFill(['user_id' => $user->id])->save();

            return ['user' => $user, 'password' => $plain, 'existed' => false];
        });
    }
}
