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
            $table->text('justification')->nullable()->after('status');
            $table->boolean('is_early')->default(false)->after('justification');
            $table->boolean('is_late')->default(false)->after('is_early');
            $table->time('expected_time')->nullable()->after('is_late');
            $table->integer('minutes_difference')->nullable()->after('expected_time');
            $table->string('punch_type')->nullable()->after('minutes_difference'); // 'entry' or 'exit'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['justification', 'is_early', 'is_late', 'expected_time', 'minutes_difference', 'punch_type']);
        });
    }
};