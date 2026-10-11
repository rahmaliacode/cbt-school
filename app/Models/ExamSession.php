<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $guarded = [];

    protected $casts = ['configuration_snapshot' => 'array', 'question_snapshot' => 'array', 'started_at' => 'datetime', 'ends_at' => 'datetime', 'results_locked_at' => 'datetime', 'shuffle_questions' => 'boolean', 'shuffle_options' => 'boolean', 'show_live_score' => 'boolean', 'correct_score' => 'decimal:2', 'incorrect_score' => 'decimal:2', 'blank_score' => 'decimal:2', 'question_count' => 'integer', 'duration_minutes' => 'integer', 'option_count' => 'integer'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class);
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
