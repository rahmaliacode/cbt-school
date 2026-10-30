<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    // Question banks per subject; a session draws its package from one of them.
    public const SETS = ['main' => 'Utama', 'draw' => 'Draw (tie-breaker)'];

    public function options()
    {
        return $this->hasMany(AnswerOption::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
