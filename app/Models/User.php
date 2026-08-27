<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'last_login_at',
        'current_school_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function schools()
    {
        return $this->belongsToMany(School::class);
    }

    public function currentSchool()
    {
        return $this->belongsTo(School::class, 'current_school_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function studentProfile()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function guardianProfile()
    {
        return $this->hasOne(Guardian::class, 'user_id');
    }
    
    public function children()
    {
        // A user can be a Guardian. A Guardian has many Students. Each Student has a User.
        // Return a query builder or collection of Users who are students of this guardian.
        $guardian = $this->guardianProfile;
        if ($guardian) {
            $studentIds = $guardian->students()->pluck('user_id')->filter();
            return User::whereIn('id', $studentIds);
        }
        
        return User::whereRaw('1 = 0'); // Empty query
    }
}
