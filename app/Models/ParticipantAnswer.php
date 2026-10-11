<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantAnswer extends Model
{
    protected $guarded = [];

    protected $casts = ['is_flagged' => 'boolean'];
}
