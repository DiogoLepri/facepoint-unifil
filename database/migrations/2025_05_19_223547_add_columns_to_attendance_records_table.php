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
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('attendance_records', 'entry_time')) {
                $table->dateTime('entry_time')->nullable();
            }
            
            if (!Schema::hasColumn('attendance_records', 'exit_time')) {
                $table->dateTime('exit_time')->nullable();
            }
            
            if (!Schema::hasColumn('attendance_records', 'status')) {
                $table->string('status')->default('registered');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Remover colunas caso precise reverter
            $table->dropColumn(['entry_time', 'exit_time', 'status']);
            
            // Se quiser remover também o user_id, descomente a linha abaixo
            // $table->dropForeign(['user_id']);
            // $table->dropColumn('user_id');
        });
    }
};