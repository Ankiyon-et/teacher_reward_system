<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'profile_picture', 'balance',
        'average_rating', 'total_rewards', 'status',
        'hire_date', 'school_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'teacher_grade','teacher_id', 'grade_id');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}
