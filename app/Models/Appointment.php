<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'client_id',
        'title',
        'services',
        'service_other',
        'train_number',
        'location',
        'description',
        'start_time',
        'end_time',
        'status',
        'actual_start',
        'actual_end',
        'travel_out_start',
        'travel_out_end',
        'travel_back_start',
        'travel_back_end',
        'pause_minutes',
        'report_notes',
        'report_status',
        'reported_at',
    ];

    protected $casts = [
        'services' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function user() {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
