<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['teacher_id', 'academic_term_id', 'title', 'description', 'type', 'quantity', 'open_date', 'close_date', 'status'])]
class Reservation extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'open_date' => 'date',
            'close_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * Get all available reservation types.
     */
    public static function types(): array
    {
        return [
            'printed_notes' => 'ملاحظات مطبوعة',
            'books' => 'كتب',
            'booklets' => 'بوكليت',
            'practice_sheets' => 'أوراق تدريب',
            'external_material' => 'مادة خارجية',
            'other' => 'أخرى',
        ];
    }

    /**
     * Get the human-readable type label.
     */
    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? 'أخرى';
    }

    /**
     * Check if the reservation is unlimited (no quantity set).
     */
    public function isUnlimited(): bool
    {
        return empty($this->quantity) || $this->quantity === 0;
    }

    /**
     * Check if the reservation is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the reservation is open.
     */
    public function isOpen(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }
        $now = now()->startOfDay();
        if ($this->open_date && $now->lt($this->open_date)) {
            return false;
        }
        if ($this->close_date && $now->gt($this->close_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the reservation is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if the reservation is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'reservation_id');
    }

    public function targetGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'reservation_groups', 'reservation_id', 'group_id')
            ->withTimestamps();
    }

    /**
     * Active (non-cancelled) reservations count towards the quantity.
     * Sprint 63: cancelled items are excluded so quantity is restored.
     */
    public function reservedCount(): int
    {
        return $this->items()->where('delivery_status', '!=', 'cancelled')->count();
    }

    public function deliveredCount(): int
    {
        return $this->items()->where('delivery_status', 'delivered')->count();
    }

    public function notDeliveredCount(): int
    {
        return $this->items()->where('delivery_status', 'pending')->count();
    }

    /**
     * Get remaining quantity. Returns -1 for unlimited reservations.
     */
    public function remaining(): int
    {
        if ($this->isUnlimited()) {
            return -1; // -1 indicates unlimited
        }
        return max(0, $this->quantity - $this->reservedCount());
    }

    /**
     * Get quantity display (for views).
     */
    public function quantityDisplay(): string
    {
        return $this->isUnlimited() ? 'غير محدود' : (string) $this->quantity;
    }
}