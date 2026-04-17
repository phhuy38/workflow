<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StepDefinition extends Model
{
    use SoftDeletes;

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

    public function processTemplate(): BelongsTo
    {
        return $this->belongsTo(ProcessTemplate::class, 'template_id');
    }
}
