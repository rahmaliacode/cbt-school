<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Each subject keeps a main question bank and a separate draw (tie-breaker) bank; a session picks one.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_set', 20)->default('main');
        });
        // Add the new unique index before dropping the old one: MySQL keeps it as the subject_id foreign key index.
        Schema::table('questions', fn (Blueprint $table) => $table->unique(['subject_id', 'question_set', 'number']));
        Schema::table('questions', fn (Blueprint $table) => $table->dropUnique(['subject_id', 'number']));
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('question_set', 20)->default('main');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', fn (Blueprint $table) => $table->dropColumn('question_set'));
        Schema::table('questions', fn (Blueprint $table) => $table->unique(['subject_id', 'number']));
        Schema::table('questions', fn (Blueprint $table) => $table->dropUnique(['subject_id', 'question_set', 'number']));
        Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('question_set'));
    }
};
