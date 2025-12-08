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
        // Add indexes for better query performance
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasIndex('assignments', 'assignments_user_id_deadline_index')) {
                $table->index(['user_id', 'deadline'], 'assignments_user_id_deadline_index');
            }
            if (!Schema::hasIndex('assignments', 'assignments_user_id_is_done_index')) {
                $table->index(['user_id', 'is_done'], 'assignments_user_id_is_done_index');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasIndex('schedules', 'schedules_user_id_date_index')) {
                $table->index(['user_id', 'date'], 'schedules_user_id_date_index');
            }
            if (!Schema::hasIndex('schedules', 'schedules_user_id_is_completed_index')) {
                $table->index(['user_id', 'is_completed'], 'schedules_user_id_is_completed_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', 'users_username_index')) {
                $table->index('username', 'users_username_index');
            }
            if (!Schema::hasIndex('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_user_id_deadline_index');
            $table->dropIndex('assignments_user_id_is_done_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_user_id_date_index');
            $table->dropIndex('schedules_user_id_is_completed_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_index');
            $table->dropIndex('users_email_index');
        });
    }
};
