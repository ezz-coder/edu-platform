<?php

namespace Tests\Feature;

use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\ResourceTarget;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sprint 63 — Student Reservations cancellation + Student Resources search/filter/download.
 */
class Sprint63StudentReservationsResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private StudyGroup $group;

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

        $this->student = User::create([
            'name' => 'Student One',
            'email' => 'student1@test.local',
            'password' => 'password',
            'role' => 'student',
            'status' => 'active',
            'mobile' => '0100000000',
        ]);

        $this->group = StudyGroup::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Group A',
            'day' => 'sunday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'gender_type' => 'boys',
            'grade_level' => 'grade7',
            'status' => 'active',
        ]);

        GroupEnrollment::create([
            'student_id' => $this->student->id,
            'group_id' => $this->group->id,
            'status' => 'enrolled',
        ]);
    }

    private function asStudent(User $user = null)
    {
        $user = $user ?? $this->student;

        return $this->actingAs($user)->withSession(['active_student_id' => $user->id]);
    }

    private function makeReservation(int $quantity = null): Reservation
    {
        $reservation = Reservation::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'مذكرة الشهر الأول',
            'type' => 'printed_notes',
            'quantity' => $quantity,
            'open_date' => now()->subDay()->toDateString(),
            'close_date' => now()->addWeek()->toDateString(),
            'status' => 'open',
        ]);
        $reservation->targetGroups()->attach($this->group->id);

        return $reservation;
    }

    // ---------------- Reservation Cancellation ----------------

    public function test_student_can_cancel_own_reservation_and_quantity_is_restored(): void
    {
        $reservation = $this->makeReservation(1);

        // Reserve → auto-close because quantity reaches zero.
        $this->asStudent()->post(route('student.reservations.reserve', $reservation));
        $item = ReservationItem::where('reservation_id', $reservation->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($item);
        $reservation->refresh();
        $this->assertEquals(0, $reservation->remaining());
        $this->assertEquals('closed', $reservation->status);

        // Cancel.
        $response = $this->asStudent()->post(route('student.reservations.cancel', $item));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $reservation->refresh();

        $this->assertTrue($item->isCancelled());
        $this->assertEquals(1, $reservation->remaining());          // quantity restored
        $this->assertEquals('open', $reservation->status);           // auto-close reversed

        // Teacher notification created exactly once.
        $notifications = Notification::where('teacher_id', $this->teacher->id)
            ->where('type', 'reservation')
            ->where('title', 'إلغاء حجز من طالب')
            ->get();
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString($this->student->name, $notifications[0]->message);
        $this->assertStringContainsString($reservation->title, $notifications[0]->message);

        // History reflects the cancellation.
        $historyItem = ReservationItem::where('student_id', $this->student->id)
            ->where('delivery_status', 'cancelled')->first();
        $this->assertNotNull($historyItem);
    }

    public function test_cancelling_unlimited_reservation_does_not_change_status_and_notifies_once(): void
    {
        $reservation = $this->makeReservation(0); // unlimited (quantity NOT NULL, 0 = unlimited)

        $this->asStudent()->post(route('student.reservations.reserve', $reservation));
        $item = ReservationItem::where('reservation_id', $reservation->id)->first();

        $this->asStudent()->post(route('student.reservations.cancel', $item))->assertSessionHas('success');

        $reservation->refresh();
        $this->assertEquals('open', $reservation->status); // unlimited stays open
        $this->assertCount(
            1,
            Notification::where('teacher_id', $this->teacher->id)
                ->where('title', 'إلغاء حجز من طالب')->get()
        );
    }

    public function test_student_cannot_cancel_another_students_reservation(): void
    {
        $other = User::create([
            'name' => 'Student Two',
            'email' => 'student2@test.local',
            'password' => 'password',
            'role' => 'student',
            'status' => 'active',
            'mobile' => '0111111111',
        ]);

        $reservation = $this->makeReservation(5);
        $item = ReservationItem::create([
            'reservation_id' => $reservation->id,
            'student_id' => $other->id,
            'group_id' => $this->group->id,
            'reserved_at' => now(),
            'delivery_status' => 'pending',
        ]);

        $this->asStudent() // logged in as student one
            ->post(route('student.reservations.cancel', $item))
            ->assertForbidden();

        $this->assertFalse($item->fresh()->isCancelled());
    }

    public function test_invalid_reservation_item_id_returns_404(): void
    {
        $this->asStudent()
            ->post(route('student.reservations.cancel', 999999))
            ->assertNotFound();
    }

    public function test_delivered_reservation_cannot_be_cancelled(): void
    {
        $reservation = $this->makeReservation(5);
        $item = ReservationItem::create([
            'reservation_id' => $reservation->id,
            'student_id' => $this->student->id,
            'group_id' => $this->group->id,
            'reserved_at' => now(),
            'delivery_status' => 'delivered',
        ]);

        $this->asStudent()
            ->post(route('student.reservations.cancel', $item))
            ->assertSessionHas('error');

        $this->assertTrue($item->fresh()->isDelivered());
        $this->assertCount(0, Notification::where('title', 'إلغاء حجز من طالب')->get());
    }

    public function test_double_cancel_is_rejected_without_duplicate_notification(): void
    {
        $reservation = $this->makeReservation(5);
        $item = ReservationItem::create([
            'reservation_id' => $reservation->id,
            'student_id' => $this->student->id,
            'group_id' => $this->group->id,
            'reserved_at' => now(),
            'delivery_status' => 'pending',
        ]);

        $this->asStudent()->post(route('student.reservations.cancel', $item))->assertSessionHas('success');
        $this->asStudent()->post(route('student.reservations.cancel', $item))->assertSessionHas('error');

        $this->assertCount(
            1,
            Notification::where('teacher_id', $this->teacher->id)
                ->where('title', 'إلغاء حجز من طالب')->get()
        );
    }

    public function test_student_can_re_reserve_after_cancelling(): void
    {
        $reservation = $this->makeReservation(1);

        $this->asStudent()->post(route('student.reservations.reserve', $reservation));
        $item = ReservationItem::where('reservation_id', $reservation->id)->first();
        $this->asStudent()->post(route('student.reservations.cancel', $item));

        // Re-reserve must succeed (cancelled item no longer blocks).
        $this->asStudent()->post(route('student.reservations.reserve', $reservation))->assertSessionHas('success');

        $this->assertEquals(
            1,
            ReservationItem::where('reservation_id', $reservation->id)
                ->where('delivery_status', 'pending')->count()
        );
    }

    // ---------------- Resource Search & Filters ----------------

    private function makeResource(string $title, string $type, array $target = ['scope' => 'all'], ?string $lessonId = null, ?string $publishDate = null): Resource
    {
        $resource = Resource::create([
            'teacher_id' => $this->teacher->id,
            'lesson_id' => $lessonId,
            'title' => $title,
            'type' => $type,
            'file_path' => in_array($type, ['pdf', 'image', 'zip', 'other']) ? "resources/{$title}.pdf" : null,
            'publish_date' => $publishDate ?? now()->toDateString(),
            'status' => 'visible',
        ]);
        ResourceTarget::create(array_merge(['resource_id' => $resource->id], $target));

        return $resource;
    }

    public function test_search_by_title_matches_only_matching_resources(): void
    {
        $match = $this->makeResource('الرياضيات الفصل الأول', 'pdf');
        $noMatch = $this->makeResource('الفيزياء', 'pdf');

        $response = $this->asStudent()->get(route('student.resources.home', ['q' => 'الرياضيات']));
        $response->assertOk();
        $response->assertSee('الرياضيات الفصل الأول');
        $response->assertDontSee('الفيزياء');
    }

    public function test_search_with_non_matching_query_returns_empty_results(): void
    {
        $this->makeResource('الرياضيات', 'pdf');

        $response = $this->asStudent()->get(route('student.resources.home', ['q' => 'كيمياء']));
        $response->assertOk();
        $response->assertSee('لا توجد نتائج');
    }

    public function test_empty_query_shows_all_visible_resources(): void
    {
        $a = $this->makeResource('مورد أ', 'pdf');
        $b = $this->makeResource('مورد ب', 'google_drive');

        $response = $this->asStudent()->get(route('student.resources.home'));
        $response->assertOk();
        $response->assertSee('مورد أ');
        $response->assertSee('مورد ب');
    }

    public function test_filter_by_type(): void
    {
        $pdf = $this->makeResource('ملف بي دي إف', 'pdf');
        $drive = $this->makeResource('رابط درايف', 'google_drive');

        $response = $this->asStudent()->get(route('student.resources.home', ['type' => 'google_drive']));
        $response->assertOk();
        $response->assertSee('رابط درايف');
        $response->assertDontSee('ملف بي دي إف');
    }

    public function test_filter_by_publish_date_range(): void
    {
        $old = $this->makeResource('قديم', 'pdf', publishDate: now()->subDays(30)->toDateString());
        $new = $this->makeResource('جديد', 'pdf', publishDate: now()->toDateString());

        $from = now()->subDays(7)->toDateString();
        $to = now()->toDateString();

        $response = $this->asStudent()->get(route('student.resources.home', [
            'date_from' => $from, 'date_to' => $to,
        ]));
        $response->assertOk();
        $response->assertSee('جديد');
        $response->assertDontSee('قديم');
    }

    public function test_filters_do_not_expose_invisible_or_untargeted_resources(): void
    {
        // Hidden resource matching the search query must NOT appear.
        $hidden = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'سري جداً',
            'type' => 'pdf',
            'publish_date' => now()->toDateString(),
            'status' => 'hidden',
        ]);
        ResourceTarget::create(['resource_id' => $hidden->id, 'scope' => 'all']);

        // Visible but targeted at another gender group.
        $girlsOnly = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'بنات فقط',
            'type' => 'pdf',
            'publish_date' => now()->toDateString(),
            'status' => 'visible',
        ]);
        ResourceTarget::create(['resource_id' => $girlsOnly->id, 'scope' => 'gender', 'gender' => 'girls']);

        $response = $this->asStudent()->get(route('student.resources.home', ['q' => 'فقط']));
        $response->assertOk();
        $response->assertDontSee('سري جداً');
        $response->assertDontSee('بنات فقط');
    }

    // ---------------- Direct Download ----------------

    public function test_direct_download_of_allowed_file_resource(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/test.pdf', '%PDF-1.4 fake content');

        $resource = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'تحميل مباشر',
            'type' => 'pdf',
            'file_path' => 'resources/test.pdf',
            'publish_date' => now()->toDateString(),
            'status' => 'visible',
        ]);
        ResourceTarget::create(['resource_id' => $resource->id, 'scope' => 'all']);

        $response = $this->asStudent()->get(route('student.resources.download', $resource));
        $response->assertOk();
        $this->assertTrue($response->headers->has('Content-Disposition'));
    }

    public function test_download_denied_for_invisible_resource(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/hidden.pdf', 'secret');

        $resource = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'مخفي',
            'type' => 'pdf',
            'file_path' => 'resources/hidden.pdf',
            'publish_date' => now()->toDateString(),
            'status' => 'hidden',
        ]);
        ResourceTarget::create(['resource_id' => $resource->id, 'scope' => 'all']);

        $this->asStudent()->get(route('student.resources.download', $resource))->assertForbidden();
    }

    public function test_download_denied_for_expired_resource(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/expired.pdf', 'old');

        $resource = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'منتهي',
            'type' => 'pdf',
            'file_path' => 'resources/expired.pdf',
            'publish_date' => now()->subDays(10)->toDateString(),
            'expires_at' => now()->subDay()->toDateString(),
            'status' => 'visible',
        ]);
        ResourceTarget::create(['resource_id' => $resource->id, 'scope' => 'all']);

        $this->asStudent()->get(route('student.resources.download', $resource))->assertForbidden();
    }

    public function test_download_denied_for_untargeted_resource(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('resources/girls.pdf', 'x');

        $resource = Resource::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'بنات فقط',
            'type' => 'pdf',
            'file_path' => 'resources/girls.pdf',
            'publish_date' => now()->toDateString(),
            'status' => 'visible',
        ]);
        ResourceTarget::create(['resource_id' => $resource->id, 'scope' => 'gender', 'gender' => 'girls']);

        $this->asStudent()->get(route('student.resources.download', $resource))->assertForbidden();
    }

    public function test_non_file_resource_is_not_treated_as_downloadable(): void
    {
        $drive = $this->makeResource('درايف', 'google_drive');

        $this->asStudent()->get(route('student.resources.download', $drive))->assertNotFound();
    }

    public function test_guest_cannot_access_download(): void
    {
        $resource = $this->makeResource('عام', 'pdf');

        $this->get(route('student.resources.download', $resource))->assertRedirect(route('login'));
    }
}