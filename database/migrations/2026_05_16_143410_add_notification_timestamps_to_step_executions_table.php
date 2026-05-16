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
        Schema::table('step_executions', function (Blueprint $table) {
            $table->timestamp('unacknowledged_notified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('step_executions', function (Blueprint $table) {
            $table->dropColumn(['unacknowledged_notified_at']);
        });
    }
};
