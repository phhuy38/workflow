<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessInstance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'template_snapshot_data',
        'status',
        'launched_at',
        'completed_at',
        'launched_by',
    ];

    protected function casts(): array
    {
        return [
            'template_snapshot_data' => 'array',
            'launched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'launched_by');
    }
}
