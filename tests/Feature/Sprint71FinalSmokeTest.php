<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 71 — Final Sign-off Smoke Test.
 * Lightweight proof that every role can authenticate and reach its
 * main pages. Full workflow QA was completed in Sprints 63–70.
 */
class Sprint71FinalSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Smoke',
            'email' => $role . '.smoke@test.local',
            'mobile' => '0100000000' . rand(10, 99),
            'password' => bcrypt('Password@123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_admin_login_dashboard_and_navigation(): void
    {
        $admin = $this->makeUser('admin');

        $this->post('/login', [
            'mobile' => $admin->mobile,
            'password' => 'Password@123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_teacher_login_and_main_pages(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->post('/login', [
            'mobile' => $teacher->mobile,
            'password' => 'Password@123',
        ])->assertRedirect(route('teacher.dashboard'));

        $pages = [
            'teacher.dashboard', 'teacher.groups.index', 'teacher.students.index',
            'teacher.attendance.index', 'teacher.lessons.index', 'teacher.resources.index',
            'teacher.exams.index', 'teacher.grades.index', 'teacher.reservations.index',
            'teacher.reports.index', 'teacher.settings.index', 'notifications.index',
        ];
        foreach ($pages as $page) {
            $this->actingAs($teacher)->get(route($page))->assertOk();
        }
    }

    public function test_student_login_and_main_pages(): void
    {
        $student = $this->makeUser('student');

        // Single-account students land on the resources home (per AuthenticatedSessionController).
        $this->post('/login', [
            'mobile' => $student->mobile,
            'password' => 'Password@123',
        ])->assertRedirect(route('student.resources.home'));

        $pages = [
            'student.dashboard', 'student.account', 'student.groups.index',
            'student.resources.home', 'student.exams.index', 'student.grades',
            'student.attendance', 'student.reservations.index', 'notifications.index',
            'student.settings',
        ];
        foreach ($pages as $page) {
            $this->actingAs($student)->get(route($page))->assertOk();
        }
    }

    public function test_role_middleware_blocks_cross_role_access(): void
    {
        $student = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');

        $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.dashboard'))->assertForbidden();
    }
}