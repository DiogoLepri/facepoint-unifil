<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migração remove campos obsoletos que não são mais utilizados pelo sistema:
     * - users.face_data: substituído pela tabela recognition_records
     * - users.entry_time, exit_time, days_of_week: substituídos pelo campo schedule (JSON)
     * - attendance_records.justification: funcionalidade não implementada na interface
     */
    public function up(): void
    {
        // Remover campos obsoletos da tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_data',      // Nunca armazenado, usa recognition_records
                'entry_time',     // Substituído por schedule
                'exit_time',      // Substituído por schedule
                'days_of_week'    // Substituído por schedule
            ]);
        });

        // Remover campo justification da tabela attendance_records
        // (campo existe mas não há interface para usuário preencher)
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('justification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar campos na tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->text('face_data')->nullable();
            $table->time('entry_time')->nullable()->default('14:00:00');
            $table->time('exit_time')->nullable()->default('18:00:00');
            $table->json('days_of_week')->nullable();
        });

        // Restaurar campo justification
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('justification')->nullable();
        });
    }
};
