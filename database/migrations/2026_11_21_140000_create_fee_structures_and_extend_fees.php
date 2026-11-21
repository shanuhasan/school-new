<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Class-wise fee definition: "Class I → Tuition ₹1000, monthly" for a session
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->string('title');                 // Tuition Fee, Transport Fee...
            $table->decimal('amount', 10, 2);        // amount per period
            $table->string('frequency');             // monthly, quarterly, yearly
            $table->timestamps();

            $table->unique(['academic_session_id', 'school_class_id', 'title'], 'fee_structure_unique');
        });

        // Each generated installment lives in the existing fees table, tagged with its structure/period/class
        Schema::table('fees', function (Blueprint $table) {
            $table->foreignId('fee_structure_id')->nullable()->after('academic_session_id')->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('fee_structure_id')->constrained()->nullOnDelete();
            $table->string('period')->nullable()->after('title');       // "April 2026", "Q1 (Apr–Jun)", "Annual"
            $table->unsignedSmallInteger('period_no')->nullable()->after('period');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_structure_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropColumn(['period', 'period_no']);
        });
        Schema::dropIfExists('fee_structures');
    }
};
