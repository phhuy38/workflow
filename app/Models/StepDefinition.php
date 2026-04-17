<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class StepDefinition extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'template_id',
        'name',
        'description',
        'order',
        'assignee_type',
        'assignee_id',
        'duration_hours',
        'is_required',
        'config_data',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'config_data' => 'array',
        'duration_hours' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'description', 'order', 'assignee_type', 'assignee_id', 'duration_hours', 'is_required']);
    }

    public function processTemplate(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class, 'template_id');
    }
}
