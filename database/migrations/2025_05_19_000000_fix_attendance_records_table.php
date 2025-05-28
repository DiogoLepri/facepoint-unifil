<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simplificado para evitar erros
        if (Schema::hasTable('attendance_records')) {
            if (!Schema::hasColumn('attendance_records', 'entry_time')) {
                Schema::table('attendance_records', function (Blueprint $table) {
                    $table->dateTime('entry_time')->nullable();
                });
            }
            
            if (!Schema::hasColumn('attendance_records', 'exit_time')) {
                Schema::table('attendance_records', function (Blueprint $table) {
                    $table->dateTime('exit_time')->nullable();
                });
            }
            
            if (!Schema::hasColumn('attendance_records', 'status')) {
                Schema::table('attendance_records', function (Blueprint $table) {
                    $table->string('status')->nullable();
                });
            }
            
            if (!Schema::hasColumn('attendance_records', 'user_id')) {
                Schema::table('attendance_records', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // Não fazer nada no down
    }
};