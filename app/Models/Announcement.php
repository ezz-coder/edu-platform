<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['teacher_id', 'title', 'body', 'published_at'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'announcement_targets', 'announcement_id', 'group_id');
    }

    /**
     * Does this announcement target the given group?
     */
    public function targetsGroup(StudyGroup $group): bool
    {
        return $this->groups()->where('study_groups.id', $group->id)->exists();
    }

    /**
     * Announcements visible to a student of the given active enrollment.
     */
    public static function visibleForEnrollment(GroupEnrollment $enrollment, int $limit = 5)
    {
        return self::where('teacher_id', $enrollment->group->teacher_id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('groups', fn ($q) => $q->where('study_groups.id', $enrollment->group_id))
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}