<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('exam_date')->nullable();
            $table->decimal('max_marks', 8, 2)->default(100);
            $table->decimal('passing_marks', 8, 2)->default(40);
            $table->timestamps();
            
            // Allow querying efficiently, ensure uniqueness
            $table->unique(['exam_id', 'school_class_id', 'section_id', 'subject_id'], 'exam_subj_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_subjects');
    }
};
