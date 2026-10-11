<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerOption extends Model
{
    protected $guarded = [];

    protected $casts = ['is_correct' => 'boolean'];

    public $timestamps = false;
}
