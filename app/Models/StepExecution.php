<?php

namespace App\Models;

use App\States\StepExecution\StepExecutionState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class StepExecution extends Model
{
    use HasFactory, HasStates;

    protected $fillable = [
        'instance_id',
        'step_definition_id',
        'step_snapshot_data',
        'name',
        'order',
        'status',
        'assigned_to',
        'started_at',
        'deadline_at',
        'completed_at',
        'completed_by',
        'completion_notes',
        'deadline_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'step_snapshot_data' => 'array',
            'status' => StepExecutionState::class,
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'deadline_notified_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(ProcessInstance::class, 'instance_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(StepDefinition::class, 'step_definition_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function finisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
