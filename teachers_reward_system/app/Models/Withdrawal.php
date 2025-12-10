<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = ['teacher_id', 'amount', 'status', 'completed_at'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
