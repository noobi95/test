<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'company_id',
        'original_filename',
        'file_path',
        'file_size',
        'uploaded_by',
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_document')->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}