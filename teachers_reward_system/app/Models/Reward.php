<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = ['parent_name', 'teacher_id', 'amount'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
