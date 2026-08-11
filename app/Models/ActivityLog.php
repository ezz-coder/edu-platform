<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'actor_id',
        'actor_type',
        'actor_type_label',
        'action',
        'category',
        'description',
        'ip_address',
        'owner_id',
        'owner_type',
    ];

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public static function log(array $data): self
    {
        $data['ip_address'] ??= request()->ip();

        return self::create($data);
    }
}