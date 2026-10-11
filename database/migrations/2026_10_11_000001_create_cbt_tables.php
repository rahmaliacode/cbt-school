<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_admin')->default(false));
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('number');
            $t->text('text');
            $t->timestamps();
            $t->unique(['subject_id', 'number']);
        });
        Schema::create('answer_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('question_id')->constrained()->cascadeOnDelete();
            $t->string('label', 1);
            $t->text('text');
            $t->boolean('is_correct')->default(false);
            $t->unique(['question_id', 'label']);
        });
        Schema::create('participants', function (Blueprint $t) {
            $t->id();
            $t->string('participant_number')->unique();
            $t->string('name');
            $t->string('school')->nullable();
            $t->timestamps();
        });
        Schema::create('exam_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('token')->unique();
            $t->string('status')->default('draft')->index();
            $t->unsignedInteger('question_count');
            $t->unsignedInteger('duration_minutes');
            $t->decimal('correct_score', 10, 2)->default(4);
            $t->decimal('incorrect_score', 10, 2)->default(-1);
            $t->decimal('blank_score', 10, 2)->default(0);
            $t->unsignedTinyInteger('option_count')->default(4);
            $t->string('tie_breaker')->default('none');
            $t->boolean('shuffle_questions')->default(false);
            $t->boolean('shuffle_options')->default(false);
            $t->boolean('show_live_score')->default(false);
            $t->json('configuration_snapshot')->nullable();
            $t->json('question_snapshot')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ends_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('exam_session_participant', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $t->unique(['exam_session_id', 'participant_id']);
        });
        Schema::create('attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('participant_id')->constrained()->restrictOnDelete();
            $t->json('question_order');
            $t->json('option_order');
            $t->string('status')->default('in_progress')->index();
            $t->timestamp('started_at');
            $t->timestamp('deadline_at')->index();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamps();
            $t->unique(['exam_session_id', 'participant_id']);
        });
        Schema::create('participant_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('question_id')->constrained()->restrictOnDelete();
            $t->foreignId('answer_option_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_flagged')->default(false);
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
            $t->unique(['attempt_id', 'question_id']);
        });
        Schema::create('results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedInteger('correct_count');
            $t->unsignedInteger('incorrect_count');
            $t->unsignedInteger('blank_count');
            $t->decimal('total_score', 12, 2);
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');
            $t->json('context');
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'results', 'participant_answers', 'attempts', 'exam_session_participant', 'exam_sessions', 'participants', 'answer_options', 'questions', 'subjects'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
