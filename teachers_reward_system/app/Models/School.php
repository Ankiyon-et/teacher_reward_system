<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = ['name', 'logo', 'description', 'address', 'contact_email'];

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function admins()
    {
        return $this->hasMany(SchoolAdmin::class);
    }

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'school_grades');
    }
}
