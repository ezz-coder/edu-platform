<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'student_id', 'group_id', 'requested_group_id', 'academic_term_id',
    'card_id', 'qr_token', 'status', 'inactivated_at',
])]
class GroupEnrollment extends Model
{
    protected function casts(): array
    {
        return [
            'inactivated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function requestedGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'requested_group_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    /**
     * Immutable log of group transfers / status changes.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(EnrollmentHistory::class, 'enrollment_id')
            ->orderBy('changed_at');
    }

    /**
     * Whether the student is currently active in this enrollment.
     */
    public function isActive(): bool
    {
        return $this->status === 'enrolled';
    }

    /**
     * Whether the student is inactive (manual or absence removal).
     */
    public function isInactive(): bool
    {
        return in_array($this->status, ['removed', 'inactive_due_to_absence', 'inactive_due_to_teacher']);
    }

    /**
     * Record the initial registration event. Called once at creation.
     */
    public function recordRegistered(string $performedBy = 'النظام'): void
    {
        $this->histories()->create([
            'student_id' => $this->student_id,
            'from_group_id' => $this->group_id,
            'to_group_id' => $this->group_id,
            'reason' => 'registered',
            'action' => 'registered',
            'performed_by' => $performedBy,
            'changed_at' => $this->created_at ?? now(),
        ]);
    }

    /**
     * Manual removal by teacher (Sprint 20: inactive_due_to_teacher).
     * Identity & history preserved; future access revoked only.
     */
    public function markRemovedByTeacher(string $performedBy = 'المدرس'): void
    {
        $this->update([
            'status' => 'inactive_due_to_teacher',
            'inactivated_at' => $this->inactivated_at ?? now(),
        ]);

        $this->histories()->create([
            'student_id' => $this->student_id,
            'from_group_id' => $this->group_id,
            'to_group_id' => $this->group_id,
            'reason' => 'manual_removal',
            'action' => 'removed',
            'performed_by' => $performedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Mark the student as inactive due to absence (from attendance rule).
     */
    public function markInactive(string $reason = 'inactivated'): void
    {
        $this->update([
            'status' => 'inactive_due_to_absence',
            'inactivated_at' => $this->inactivated_at ?? now(),
        ]);

        $this->histories()->create([
            'student_id' => $this->student_id,
            'from_group_id' => $this->group_id,
            'to_group_id' => $this->group_id,
            'reason' => 'attendance_removal',
            'action' => 'attendance_removal',
            'performed_by' => 'النظام (الحضور)',
            'changed_at' => now(),
        ]);
    }

    /**
     * Restore the student to active and clear the inactivation date.
     */
    public function restoreActive(string $performedBy = 'المدرس'): void
    {
        $this->update([
            'status' => 'enrolled',
            'inactivated_at' => null,
        ]);

        $this->histories()->create([
            'student_id' => $this->student_id,
            'from_group_id' => $this->group_id,
            'to_group_id' => $this->group_id,
            'reason' => 'restored',
            'action' => 'restored',
            'performed_by' => $performedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Record a transfer / move between groups, keeping the same enrollment
     * (and therefore the same card_id + qr_token).
     */
    public function transferTo(int $toGroupId, string $performedBy = 'المدرس'): void
    {
        $fromGroupId = $this->group_id;

        $this->update([
            'group_id' => $toGroupId,
            'inactivated_at' => null,
            'status' => 'enrolled',
        ]);

        $this->histories()->create([
            'student_id' => $this->student_id,
            'from_group_id' => $fromGroupId,
            'to_group_id' => $toGroupId,
            'reason' => 'moved',
            'action' => 'moved',
            'performed_by' => $performedBy,
            'changed_at' => now(),
        ]);
    }

    /**
     * Generate an immutable card id (e.g. CARD-9F81A7B2).
     * Called once when the enrollment is created.
     */
    public static function generateCardId(): string
    {
        do {
            $id = 'CARD-' . strtoupper(Str::random(8));
        } while (self::where('card_id', $id)->exists());

        return $id;
    }

    /**
     * Generate a long random QR token (permanent for this enrollment).
     */
    public static function generateQrToken(): string
    {
        return Str::random(40);
    }
}