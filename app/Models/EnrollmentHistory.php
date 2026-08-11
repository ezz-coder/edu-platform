<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'enrollment_id',
    'student_id',
    'from_group_id',
    'to_group_id',
    'reason',
    'action',
    'performed_by',
    'changed_at',
])]
class EnrollmentHistory extends Model
{
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(GroupEnrollment::class, 'enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fromGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'from_group_id');
    }

    public function toGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'to_group_id');
    }

    /**
     * Human-readable Arabic label for the immutable action.
     */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'registered' => 'تسجيل طالب',
            'moved' => 'نقل',
            'restored' => 'استرجاع',
            'removed' => 'إزالة يدوية',
            'attendance_removal' => 'إزالة بسبب الغياب',
            default => ($this->reason ?? 'غير معروف'),
        };
    }
}