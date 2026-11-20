<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Students get a public GUID used in URLs (/students/{uuid}/edit) instead of the numeric id.
 * The numeric id stays the primary key, so every foreign key keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Backfill existing students
        DB::table('students')->whereNull('uuid')->orderBy('id')->select('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('students')->where('id', $row->id)->update(['uuid' => (string) Str::uuid7()]);
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
