<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('process_templates')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order');
            $table->enum('assignee_type', ['user', 'role', 'department'])->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->integer('duration_hours')->default(24)->comment('Must NOT be null per ADR-036 publish validation');
            $table->boolean('is_required')->default(true);
            $table->json('config_data')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['template_id', 'order']); // ADR-043: order unique per template
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_definitions');
    }
};
