<?php

namespace App\Models;

use App\States\ProcessInstance\ProcessInstanceState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

class ProcessInstance extends Model
{
    use HasFactory, HasStates, SoftDeletes;

    protected $fillable = [
        'template_id',
        'name',
        'template_snapshot_data',
        'context_data',
        'status',
        'launched_at',
        'completed_at',
        'launched_by',
        'created_for',
    ];

    protected function casts(): array
    {
        return [
            'template_snapshot_data' => 'array',
            'context_data' => 'array',
            'status' => ProcessInstanceState::class,
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

    public function stepExecutions(): HasMany
    {
        return $this->hasMany(StepExecution::class, 'instance_id');
    }
}
