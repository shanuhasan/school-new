<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Guardians and teachers get a public GUID used in URLs, like students.
 * Numeric ids stay the primary keys, so foreign keys are unaffected.
 */
return new class extends Migration
{
    private array $tables = ['guardians', 'teachers'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->after('id');
            });

            DB::table($table)->whereNull('uuid')->orderBy('id')->select('id')->chunkById(500, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid7()]);
                }
            });

            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable(false)->change();
                $t->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['uuid']);
                $t->dropColumn('uuid');
            });
        }
    }
};
