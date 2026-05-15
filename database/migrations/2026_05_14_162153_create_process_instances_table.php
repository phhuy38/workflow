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
        Schema::create('process_instances', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('template_id')
                ->constrained('process_templates')
                ->restrictOnDelete(); // AC6 requirement
            $blueprint->jsonb('template_snapshot_data'); // ADR-006: Immutable snapshot

            $blueprint->string('status')->default('running'); // running, completed, cancelled
            $blueprint->timestamp('launched_at')->useCurrent();
            $blueprint->timestamp('completed_at')->nullable();
            $blueprint->foreignId('launched_by')->constrained('users');
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_instances');
    }
};
