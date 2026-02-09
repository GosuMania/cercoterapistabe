<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'type',
        'title',
        'description',
        'content',
        'contract_type',
        'weekly_hours',
        'requirements',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'weekly_hours' => 'integer',
    ];

    /**
     * Get the center that owns the announcement.
     */
    public function center()
    {
        return $this->belongsTo(CenterProfile::class, 'center_id');
    }

    /**
     * Scope per annunci attivi
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope per annunci recruiting
     */
    public function scopeRecruiting($query)
    {
        return $query->where('type', 'recruiting');
    }

    /**
     * Scope per annunci promozionali
     */
    public function scopePromotional($query)
    {
        return $query->where('type', 'promotional');
    }
}
