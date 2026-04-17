<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the plain unique index and replace with partial index
        // that excludes soft-deleted rows (same approach as process_templates.name)
        Schema::table('step_definitions', function (Blueprint $table) {
            $table->dropUnique(['template_id', 'order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX idx_step_definitions_template_order ON step_definitions (template_id, "order") WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_step_definitions_template_order');

        Schema::table('step_definitions', function (Blueprint $table) {
            $table->unique(['template_id', 'order']);
        });
    }
};
