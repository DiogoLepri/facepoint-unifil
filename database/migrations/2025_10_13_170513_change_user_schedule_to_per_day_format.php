<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new schedule column
        Schema::table('users', function (Blueprint $table) {
            $table->json('schedule')->nullable()->after('days_of_week');
        });

        // Migrate existing data to new format
        $users = DB::table('users')->whereNotNull('days_of_week')->orWhereNotNull('entry_time')->get();

        foreach ($users as $user) {
            $schedule = [];
            $entryTime = $user->entry_time ?? '14:00';
            $exitTime = $user->exit_time ?? '18:00';

            // Get days from days_of_week or default to weekdays
            $days = $user->days_of_week ? json_decode($user->days_of_week, true) : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

            if (is_array($days)) {
                foreach ($days as $day) {
                    $schedule[$day] = [
                        'entry' => $entryTime,
                        'exit' => $exitTime
                    ];
                }
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['schedule' => json_encode($schedule)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }
};
