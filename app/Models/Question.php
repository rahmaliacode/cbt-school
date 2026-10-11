<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $guarded = [];

    public function options()
    {
        return $this->hasMany(AnswerOption::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
