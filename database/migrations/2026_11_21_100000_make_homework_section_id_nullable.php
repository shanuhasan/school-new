<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homework can be set for a whole class ("all sections"), so section_id must allow null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->foreignId('section_id')->nullable()->change();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('homework', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->foreignId('section_id')->nullable(false)->change();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
        });
    }
};
