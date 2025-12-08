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
        Schema::table('quiz_questions', function (Blueprint $table) {
            // Tambahkan kolom untuk bank soal
            $table->boolean('is_bank_question')->default(false)->after('explanation')
                ->comment('Apakah soal ini disimpan sebagai bank soal untuk dipakai ulang');
            $table->string('topic')->nullable()->after('is_bank_question')
                ->comment('Topik/kategori soal untuk memudahkan filtering');
            $table->string('difficulty_level')->nullable()->after('topic')
                ->comment('Tingkat kesulitan: easy, medium, hard');
            $table->integer('usage_count')->default(0)->after('difficulty_level')
                ->comment('Berapa kali soal ini sudah digunakan');
            $table->timestamp('last_used_at')->nullable()->after('usage_count')
                ->comment('Terakhir kali soal digunakan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn([
                'is_bank_question',
                'topic',
                'difficulty_level',
                'usage_count',
                'last_used_at'
            ]);
        });
    }
};
