<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class Sprint30AuditTest extends TestCase
{
    protected $admin;
    protected $teacher;
    protected $student;
    protected $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::where('role', 'admin')->first();
        $this->teacher = User::where('role', 'teacher')->first();
        $this->student = User::where('role', 'student')->first();
        $this->assistant = Assistant::first();
    }

    /**
     * Test public pages
     */
    public function test_public_pages()
    {
        $pages = [
            '/' => 200,
            '/login' => 200,
            '/register' => 200,
            '/forgot-password' => 200,
            '/register/teacher' => 200,
            '/register/student' => 200,
            '/up' => 200,
        ];

        foreach ($pages as $url => $expected) {
            $response = $this->get($url);
            $this->assertEquals($expected, $response->getStatusCode(), "Failed for URL: $url");
        }
    }

    /**
     * Test admin login
     */
    public function test_admin_login()
    {
        $this->assertNotNull($this->admin, 'No admin user found');
        
        $response = $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);
        
        // Should redirect to dashboard
        $response->assertStatus(302);
    }

    /**
     * Test teacher login
     */
    public function test_teacher_login()
    {
        $this->assertNotNull($this->teacher, 'No teacher user found');
        
        $response = $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'Teacher@123',
        ]);
        
        $response->assertStatus(302);
    }

    /**
     * Test student login
     */
    public function test_student_login()
    {
        $this->assertNotNull($this->student, 'No student user found');
        
        $response = $this->post('/login', [
            'email' => $this->student->email,
            'password' => 'Student@123',
        ]);
        
        $response->assertStatus(302);
    }

    /**
     * Test admin pages
     */
    public function test_admin_pages()
    {
        if (!$this->admin) {
            $this->markTestSkipped('No admin user found');
        }

        $this->actingAs($this->admin);

        $pages = [
            '/admin/dashboard' => 200,
            '/admin/teachers' => 200,
            '/admin/teacher-requests' => 200,
            '/admin/users' => 200,
            '/admin/groups' => 200,
            '/admin/settings' => 200,
            '/admin/activity-log' => 200,
            '/admin/health' => 200,
            '/admin/backup' => 200,
            '/admin/errors' => 200,
        ];

        foreach ($pages as $url => $expected) {
            $response = $this->get($url);
            $this->assertEquals($expected, $response->getStatusCode(), "Failed for URL: $url");
        }
    }

    /**
     * Test teacher pages
     */
    public function test_teacher_pages()
    {
        if (!$this->teacher) {
            $this->markTestSkipped('No teacher user found');
        }

        $this->actingAs($this->teacher);

        $pages = [
            '/teacher/dashboard' => 200,
            '/teacher/groups' => 200,
            '/teacher/groups/create' => 200,
            '/teacher/lessons' => 200,
            '/teacher/lessons/create' => 200,
            '/teacher/lessons/recycle-bin' => 200,
            '/teacher/attendance' => 200,
            '/teacher/attendance/create' => 200,
            '/teacher/students' => 200,
            '/teacher/resources' => 200,
            '/teacher/resources/create' => 200,
            '/teacher/resources/recycle-bin' => 200,
            '/teacher/reservations' => 200,
            '/teacher/reservations/create' => 200,
            '/teacher/reservations/recycle-bin' => 200,
            '/teacher/question-bank' => 200,
            '/teacher/question-bank/create' => 200,
            '/teacher/question-bank/recycle-bin' => 200,
            '/teacher/exams' => 200,
            '/teacher/exams/create' => 200,
            '/teacher/exams/recycle-bin' => 200,
            '/teacher/assistants' => 200,
            '/teacher/assistants/create' => 200,
            '/teacher/term/end' => 200,
        ];

        foreach ($pages as $url => $expected) {
            $response = $this->get($url);
            $this->assertEquals($expected, $response->getStatusCode(), "Failed for URL: $url");
        }
    }

    /**
     * Test student pages
     */
    public function test_student_pages()
    {
        if (!$this->student) {
            $this->markTestSkipped('No student user found');
        }

        $this->actingAs($this->student);

        $pages = [
            '/student/dashboard' => 200,
            '/student/groups' => 200,
            '/student/resources' => 200,
            '/student/reservations' => 200,
            '/student/reservations/history' => 200,
            '/student/exams' => 200,
            '/student/report' => 200,
            '/student/card' => 200,
            '/student/account' => 200,
            '/student/accounts' => 200,
            '/notifications' => 200,
            '/profile' => 200,
        ];

        foreach ($pages as $url => $expected) {
            $response = $this->get($url);
            $this->assertEquals($expected, $response->getStatusCode(), "Failed for URL: $url");
        }
    }

    /**
     * Test assistant pages
     */
    public function test_assistant_pages()
    {
        if (!$this->assistant) {
            $this->markTestSkipped('No assistant found');
        }

        // Assistant uses a different guard
        $response = $this->post('/assistant/login', [
            'username' => $this->assistant->username,
            'password' => 'Assistant@123',
        ]);
        
        $response->assertStatus(302);

        $pages = [
            '/assistant/dashboard' => 200,
            '/assistant/resources' => 200,
            '/assistant/resources/create' => 200,
            '/assistant/reservations' => 200,
            '/assistant/reservations/create' => 200,
            '/assistant/question-bank' => 200,
            '/assistant/question-bank/create' => 200,
            '/assistant/question-bank/recycle-bin' => 200,
            '/assistant/exams' => 200,
            '/assistant/exams/recycle-bin' => 200,
            '/assistant/groups' => 200,
            '/assistant/lessons' => 200,
            '/assistant/lessons/create' => 200,
            '/assistant/attendance' => 200,
            '/assistant/attendance/create' => 200,
            '/assistant/students' => 200,
            '/assistant/notifications' => 200,
        ];

        foreach ($pages as $url => $expected) {
            $response = $this->get($url);
            $this->assertEquals($expected, $response->getStatusCode(), "Failed for URL: $url");
        }
    }

    /**
     * Test guest access restriction
     */
    public function test_guest_access_restriction()
    {
        $protectedPages = [
            '/admin/dashboard',
            '/teacher/dashboard',
            '/student/dashboard',
            '/assistant/dashboard',
        ];

        foreach ($protectedPages as $url) {
            $response = $this->get($url);
            // Should redirect to login (302) or return 403
            $this->assertTrue(
                $response->getStatusCode() == 302 || $response->getStatusCode() == 403,
                "Failed for URL: $url - got status " . $response->getStatusCode()
            );
        }
    }

    /**
     * Test logout
     */
    public function test_logout()
    {
        if (!$this->admin) {
            $this->markTestSkipped('No admin user found');
        }

        $this->actingAs($this->admin);
        
        $response = $this->post('/logout');
        $response->assertStatus(302);
        
        // After logout, admin dashboard should redirect
        $response = $this->get('/admin/dashboard');
        $this->assertEquals(302, $response->getStatusCode(), 'Admin dashboard accessible after logout');
    }
}