<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = ['parent_name', 'teacher_id', 'value', 'comment'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
