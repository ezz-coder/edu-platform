<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['resource_id', 'scope', 'group_id', 'stage', 'gender'])]
class ResourceTarget extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }
}