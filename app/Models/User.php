<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'mobile', 'parent_mobile', 'teacher_code', 'student_code'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    /**
     * Groups owned by this teacher.
     */
    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class, 'teacher_id');
    }

    /**
     * Enrollments belonging to this student.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(GroupEnrollment::class, 'student_id');
    }

    /**
     * The active enrollment for this student under a given teacher (if any).
     */
    public function activeEnrollmentForTeacher(int $teacherId): ?GroupEnrollment
    {
        return $this->enrollments()
            ->where('status', 'enrolled')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->first();
    }

    /**
     * Generate a unique, human-readable teacher code.
     * Format: TC-XXXXXX, guaranteed unique.
     */
    public static function generateTeacherCode(): string
    {
        do {
            $code = 'TC-' . strtoupper(substr(base_convert(mt_rand(100000, 999999), 10, 36), 0, 6));
        } while (self::where('teacher_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a permanent, unique, sequential student code.
     * Format: STD-000001, guaranteed unique and never reused.
     */
    public static function generateStudentCode(): string
    {
        do {
            $max = self::whereNotNull('student_code')
                ->selectRaw("MAX(CAST(SUBSTR(student_code, 5) AS INTEGER)) as m")
                ->value('m');
            $next = (int) $max + 1;
            $code = 'STD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        } while (self::where('student_code', $code)->exists());

        return $code;
    }

    /**
     * Activity history for this teacher's workspace.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TeacherActivity::class, 'teacher_id');
    }

    /**
     * Resolve the active student account from the session.
     * The session account is the single source of truth for student pages.
     * Falls back to the first account sharing this user's mobile.
     */
    public function activeStudentAccount(): ?User
    {
        if ($this->role !== 'student') {
            return null;
        }

        $activeId = session('active_student_id');

        if ($activeId) {
            $account = self::where('id', $activeId)
                ->where('role', 'student')
                ->where('mobile', $this->mobile)
                ->first();
            if ($account) {
                return $account;
            }
        }

        // Default to the first account for this mobile.
        return self::where('role', 'student')
            ->where('mobile', $this->mobile)
            ->orderBy('id')
            ->first();
    }
}
