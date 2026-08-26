<?php

namespace Tests\Feature;

use App\Models\Assistant;
use App\Models\AssistantPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 64 — Permission-aware Assistant Sidebar, Dashboard Cards, No-Permissions fallback.
 */
class Sprint64AssistantDashboardPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Assistant $assistant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Teacher',
            'email' => 'teacher@test.local',
            'password' => 'password',
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $this->assistant = Assistant::create([
            'teacher_id' => $this->teacher->id,
            'name' => 'Assistant',
            'username' => 'assistant_test',
            'password' => 'password',
            'status' => 'active',
        ]);

        AssistantPermission::create(['assistant_id' => $this->assistant->id]);
    }

    private function setPerms(array $perms): void
    {
        $allowed = [
            'can_take_attendance', 'can_manage_students', 'can_upload_resources',
            'can_manage_reservations', 'can_restore_students', 'can_view_groups',
            'can_manage_lessons', 'can_manage_exams', 'can_manage_grades',
        ];
        $data = ['assistant_id' => $this->assistant->id];
        foreach ($allowed as $p) {
            $data[$p] = in_array($p, $perms, true);
        }
        $this->assistant->permissions()->update($data);
        $this->assistant->refresh();
    }

    private function dashboard()
    {
        return $this->actingAs($this->assistant, 'assistant')->get(route('assistant.dashboard'));
    }

    /** Case A — full permissions: all cards visible, no fallback. */
    public function test_full_permissions_shows_all_cards_and_no_fallback(): void
    {
        $this->setPerms([
            'can_take_attendance', 'can_manage_students', 'can_view_groups',
            'can_manage_lessons', 'can_upload_resources', 'can_manage_reservations',
            'can_manage_exams', 'can_manage_grades',
        ]);

        $res = $this->dashboard();
        $res->assertOk();

        foreach ([
            route('assistant.attendance.index'),
            route('assistant.students.index'),
            route('assistant.groups.index'),
            route('assistant.lessons.index'),
            route('assistant.resources.index'),
            route('assistant.reservations.index'),
            route('assistant.exams.index'),
            route('assistant.grades.index'),
            route('assistant.notifications.index'),
            route('assistant.profile.edit'),
        ] as $url) {
            $res->assertSee($url, false);
        }

        $res->assertDontSee('لا توجد صلاحيات مفعّلة لحسابك حالياً');
    }

    /** Case B — no feature permissions: fallback shown, no permission-gated links. */
    public function test_no_permissions_shows_fallback_only(): void
    {
        $res = $this->dashboard();
        $res->assertOk();
        $res->assertSee('لا توجد صلاحيات مفعّلة لحسابك حالياً');

        foreach (['attendance', 'students', 'groups', 'lessons', 'resources', 'reservations', 'exams', 'grades'] as $seg) {
            $res->assertDontSee("assistant/{$seg}", false);
        }
        // Notifications & profile remain available (no permission required).
        $res->assertSee(route('assistant.notifications.index'), false);
        $res->assertSee(route('assistant.profile.edit'), false);
    }

    /** Case C — single permission: only its card/link appears, no fallback. */
    public function test_single_permission_lessons_only(): void
    {
        $this->setPerms(['can_manage_lessons']);

        $res = $this->dashboard();
        $res->assertOk();
        $res->assertSee(route('assistant.lessons.index'), false);
        $res->assertDontSee(route('assistant.attendance.index'), false);
        $res->assertDontSee(route('assistant.exams.index'), false);
        $res->assertDontSee(route('assistant.grades.index'), false);
        $res->assertDontSee('لا توجد صلاحيات مفعّلة لحسابك حالياً');
    }

    /** Case D — multiple selective permissions. */
    public function test_multiple_selective_permissions(): void
    {
        $this->setPerms(['can_take_attendance', 'can_manage_exams']);

        $res = $this->dashboard();
        $res->assertOk();
        $res->assertSee(route('assistant.attendance.index'), false);
        $res->assertSee(route('assistant.exams.index'), false);
        $res->assertDontSee(route('assistant.lessons.index'), false);
        $res->assertDontSee(route('assistant.grades.index'), false);
        $res->assertDontSee('لا توجد صلاحيات مفعّلة لحسابك حالياً');
    }

    /** Case E — revoke permission is reflected on next render; direct URL stays protected by middleware. */
    public function test_revoke_permission_hides_card_and_blocks_direct_url(): void
    {
        $this->setPerms(['can_manage_lessons']);
        $this->dashboard()->assertSee(route('assistant.lessons.index'), false);

        // Revoke
        $this->setPerms([]);
        $this->dashboard()->assertDontSee(route('assistant.lessons.index'), false);

        // Direct URL access still blocked by middleware → 403
        $this->actingAs($this->assistant, 'assistant')
            ->get(route('assistant.lessons.index'))
            ->assertStatus(403);
    }
}