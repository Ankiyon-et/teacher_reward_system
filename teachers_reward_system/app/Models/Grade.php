<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['name'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_grades');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_grades');
    }
}
