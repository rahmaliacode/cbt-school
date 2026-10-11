<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $guarded = [];

    protected $casts = ['total_score' => 'decimal:2', 'correct_count' => 'integer', 'incorrect_count' => 'integer', 'blank_count' => 'integer'];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }
}
