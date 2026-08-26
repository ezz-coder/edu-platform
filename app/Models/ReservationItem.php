<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reservation_id', 'student_id', 'group_id', 'reserved_at', 'delivery_status', 'delivered_at'])]
class ReservationItem extends Model
{
    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Check if the item is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->delivery_status === 'delivered';
    }

    /**
     * Check if the item is pending delivery.
     */
    public function isPending(): bool
    {
        return $this->delivery_status === 'pending';
    }

    /**
     * Sprint 63: check if the student cancelled this reservation.
     */
    public function isCancelled(): bool
    {
        return $this->delivery_status === 'cancelled';
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }
}