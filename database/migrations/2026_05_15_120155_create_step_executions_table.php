<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('step_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')
                ->constrained('process_instances')
                ->cascadeOnDelete(); // ADR-043
            $table->foreignId('step_definition_id')
                ->nullable()
                ->constrained('step_definitions')
                ->nullOnDelete();
            $table->jsonb('step_snapshot_data'); // ADR-037
            $table->string('name');
            $table->integer('order');
            $table->string('status'); // Will be handled by spatie/laravel-model-states
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('completion_notes')->nullable();
            $table->timestamp('deadline_notified_at')->nullable();
            $table->timestamps();

            // ADR-042: Composite Indexes
            $table->index(['status', 'deadline_at']);
            $table->index(['assigned_to', 'status', 'deadline_at']);
            $table->index(['instance_id', 'order']);
            $table->index(['deadline_at', 'deadline_notified_at', 'status']);

            // ADR-043: Unique constraint
            $table->unique(['instance_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('step_executions');
    }
};
