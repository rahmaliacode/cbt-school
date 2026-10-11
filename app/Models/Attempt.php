<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $guarded = [];

    protected $casts = ['question_order' => 'array', 'option_order' => 'array', 'started_at' => 'datetime', 'deadline_at' => 'datetime', 'submitted_at' => 'datetime', 'last_seen_at' => 'datetime'];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function answers()
    {
        return $this->hasMany(ParticipantAnswer::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
