<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SystemActivity extends Model
{
    use HasFactory;

    protected $primaryKey = 'activity_id';

    protected $fillable = [
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }

    public static function log(
        ?int $actorUserId,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        string $description = '',
        array $metadata = []
    ): self {
        if (!Schema::hasTable((new static())->getTable())) {
            return new static([
                'actor_user_id' => $actorUserId,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        }

        return static::query()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
