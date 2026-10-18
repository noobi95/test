<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_code',
        'name_display_format',
    ];

    public function getDisplayNameAttribute(): string
    {
        return match ($this->name_display_format) {
            'short' => $this->short_code,
            'name' => $this->name,
            default => "[{$this->short_code}] {$this->name}",
        };
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'company_document')->withTimestamps();
    }
}
