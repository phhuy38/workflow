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
        Schema::table('process_instances', function (Blueprint $table) {
            $table->string('name')->after('template_id');
            $table->jsonb('context_data')->nullable()->after('template_snapshot_data');
            $table->foreignId('created_for')
                ->after('launched_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_instances', function (Blueprint $table) {
            $table->dropForeign(['created_for']);
            $table->dropColumn(['name', 'created_for']);
        });
    }
};
