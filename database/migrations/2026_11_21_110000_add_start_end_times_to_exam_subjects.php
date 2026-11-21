<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each scheduled exam subject gets a start and end date-time (an exam slot), replacing the
 * single date. The old exam_date is kept and backfilled so nothing breaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_subjects', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('subject_id');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
        });

        // Backfill: start at 09:00, end at 12:00 on the existing exam_date
        DB::table('exam_subjects')->whereNotNull('exam_date')->orderBy('id')->select('id', 'exam_date')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $date = substr((string) $row->exam_date, 0, 10);
                DB::table('exam_subjects')->where('id', $row->id)->update([
                    'starts_at' => $date . ' 09:00:00',
                    'ends_at' => $date . ' 12:00:00',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_subjects', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
