<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentChangeRequest extends Model
{
    protected $fillable = [
        'appointment_id',
        'user_id',
        'actual_start',
        'actual_end',
        'travel_out_start',
        'travel_out_end',
        'travel_back_start',
        'travel_back_end',
        'pause_minutes',
        'report_notes',
        'status',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
