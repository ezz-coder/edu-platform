<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Sprint 68 — Parent account.
 *
 * A parent is linked to students through users.parent_mobile matching
 * the parent's mobile number. The relationship is dynamic (no pivot),
 * so newly registered students with the same parent mobile are
 * automatically visible to the parent.
 */
#[Fillable(['name', 'mobile', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class ParentAccount extends Authenticatable
{
    protected $table = 'parents';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Students linked to this parent (via parent_mobile).
     */
    public function students()
    {
        return User::where('role', 'student')->where('parent_mobile', $this->mobile);
    }

    /**
     * Verify that a given student belongs to this parent.
     */
    public function ownsStudent(User $student): bool
    {
        return $student->role === 'student' && $student->parent_mobile === $this->mobile;
    }
}