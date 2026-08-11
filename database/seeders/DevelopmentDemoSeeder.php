<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Assistant;
use App\Models\AssistantPermission;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;

/**
 * Development-only demo data seeder.
 *
 * Creates a full set of demo accounts (admin, teacher, assistant, student)
 * with all required relationships so the platform can be explored end-to-end
 * in a local development environment.
 *
 * IMPORTANT SAFEGUARDS:
 * - Runs ONLY when APP_ENV=local. Never runs on production.
 * - Uses updateOrCreate so re-running never creates duplicate accounts.
 * - Does NOT delete or modify any existing real data or permissions.
 * - Does NOT touch any routes, middleware, screens, registration logic,
 *   or the permission system itself.
 */
class DevelopmentDemoSeeder extends Seeder
{
    /**
     * Demo credentials (kept here so the printed report stays in sync).
     */
    private const ADMIN_EMAIL = 'admin@bayan.local';
    private const ADMIN_PASSWORD = 'Admin@123';

    private const TEACHER_EMAIL = 'teacher@bayan.local';
    private const TEACHER_PASSWORD = 'Teacher@123';

    private const ASSISTANT_USERNAME = 'assistant@bayan.local';
    private const ASSISTANT_PASSWORD = 'Assistant@123';

    private const STUDENT_MOBILE = '01000000000';
    private const STUDENT_PASSWORD = 'Student@123';

    public function run(): void
    {
        // --- Environment guard: only run in local dev --------------------
        if (config('app.env') !== 'local') {
            $this->command->warn(
                'DevelopmentDemoSeeder skipped: it only runs when APP_ENV=local.'
            );

            return;
        }

        // --- 1) Admin ---------------------------------------------------
        // updateOrCreate keeps the existing (possibly outdated) password when
        // the record already exists, which silently breaks the documented demo
        // credentials. Re-apply the password explicitly so re-running the
        // seeder always restores the known login credentials.
        $admin = User::updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'مدير النظام',
                'email' => self::ADMIN_EMAIL,
                'mobile' => self::ADMIN_EMAIL, // login uses the mobile field
                'password' => self::ADMIN_PASSWORD,
                'role' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->password = self::ADMIN_PASSWORD;
        $admin->save();

        // --- 2) Teacher -------------------------------------------------
        $teacher = User::updateOrCreate(
            ['email' => self::TEACHER_EMAIL],
            [
                'name' => 'أحمد محمد',
                'email' => self::TEACHER_EMAIL,
                'mobile' => '01000000001', // login uses the mobile field
                'password' => self::TEACHER_PASSWORD,
                'role' => 'teacher',
                'status' => 'active',
                'teacher_code' => User::generateTeacherCode(),
            ]
        );
        $teacher->password = self::TEACHER_PASSWORD;
        $teacher->save();

        // Active academic term for the teacher (if not already present).
        $term = AcademicTerm::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ],
            [
                'teacher_id' => $teacher->id,
                'title' => 'الترم الأول',
                'year' => '2026',
                'term_number' => 1,
                'status' => 'active',
            ]
        );

        // One active study group for the teacher.
        $group = StudyGroup::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'title' => 'مجموعة تجريبية',
            ],
            [
                'teacher_id' => $teacher->id,
                'academic_term_id' => $term->id,
                'title' => 'مجموعة تجريبية',
                'description' => 'مجموعة تجريبية للتطوير',
                'day' => 'saturday',
                'start_time' => '16:00',
                'end_time' => '18:00',
                'gender_type' => 'boys',
                'grade_level' => 'الصف الأول',
                'max_students' => 30,
                'status' => 'active',
            ]
        );

        // --- 3) Assistant (linked to the teacher, ALL permissions) ------
        $assistant = Assistant::updateOrCreate(
            ['username' => self::ASSISTANT_USERNAME],
            [
                'teacher_id' => $teacher->id,
                'name' => 'محمد علي',
                'username' => self::ASSISTANT_USERNAME,
                'password' => self::ASSISTANT_PASSWORD,
                'phone' => self::STUDENT_MOBILE,
                'status' => 'active',
            ]
        );

        // Grant every permission column that actually exists in the table
        // (including any permissions added in the future) — without modifying
        // the permission system or the database schema. We read the real
        // columns so we never try to write a column that does not exist.
        $existingColumns = Schema::getColumnListing('assistant_permissions');
        $permissionKeys = array_values(array_filter(
            $existingColumns,
            fn ($col) => str_starts_with($col, 'can_')
        ));

        $permissionData = ['assistant_id' => $assistant->id];
        foreach ($permissionKeys as $key) {
            $permissionData[$key] = true;
        }
        $assistant->permissions()->updateOrCreate(
            ['assistant_id' => $assistant->id],
            $permissionData
        );

        // --- 4) Student (linked to the teacher's group) -----------------
        $student = User::updateOrCreate(
            ['mobile' => self::STUDENT_MOBILE],
            [
                'name' => 'طالب تجريبي',
                'email' => self::STUDENT_MOBILE . '@bayan.local',
                'mobile' => self::STUDENT_MOBILE,
                'parent_mobile' => self::STUDENT_MOBILE,
                'password' => self::STUDENT_PASSWORD,
                'role' => 'student',
                'status' => 'active', // required so the student can log in
                'student_code' => User::generateStudentCode(),
            ]
        );
        $student->password = self::STUDENT_PASSWORD;
        $student->save();

        // Enrollment (card + QR + active enrollment) so the student can
        // enter the platform immediately.
        $enrollment = GroupEnrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'group_id' => $group->id,
            ],
            [
                'student_id' => $student->id,
                'group_id' => $group->id,
                'academic_term_id' => $term->id,
                'card_id' => GroupEnrollment::generateCardId(),
                'qr_token' => GroupEnrollment::generateQrToken(),
                'status' => 'enrolled',
            ]
        );
        $enrollment->recordRegistered($teacher->name);

        // --- Report -----------------------------------------------------
        $this->command->line('==================================');
        $this->command->line('BAYAN DEVELOPMENT ACCOUNTS');
        $this->command->line('==================================');
        $this->command->line('');
        $this->command->line('ADMIN');
        $this->command->line('Username:');
        $this->command->line(self::ADMIN_EMAIL);
        $this->command->line('');
        $this->command->line('Password:');
        $this->command->line(self::ADMIN_PASSWORD);
        $this->command->line('');
        $this->command->line('----------------------------------');
        $this->command->line('');
        $this->command->line('TEACHER');
        $this->command->line('Username:');
        $this->command->line(self::TEACHER_EMAIL);
        $this->command->line('');
        $this->command->line('Password:');
        $this->command->line(self::TEACHER_PASSWORD);
        $this->command->line('');
        $this->command->line('----------------------------------');
        $this->command->line('');
        $this->command->line('ASSISTANT');
        $this->command->line('Username:');
        $this->command->line(self::ASSISTANT_USERNAME);
        $this->command->line('');
        $this->command->line('Password:');
        $this->command->line(self::ASSISTANT_PASSWORD);
        $this->command->line('');
        $this->command->line('----------------------------------');
        $this->command->line('');
        $this->command->line('STUDENT');
        $this->command->line('Phone:');
        $this->command->line(self::STUDENT_MOBILE);
        $this->command->line('');
        $this->command->line('Password:');
        $this->command->line(self::STUDENT_PASSWORD);
        $this->command->line('');
        $this->command->line('==================================');
        $this->command->line('Development data created successfully.');
        $this->command->line('==================================');
    }
}