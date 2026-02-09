<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'comment',
        'response',
        'response_at',
        'reported_at',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'response_at' => 'datetime',
        'reported_at' => 'datetime',
    ];

    /**
     * Get the parent reviewable model (therapist or center).
     */
    public function reviewable()
    {
        return $this->morphTo();
    }

    /**
     * Get the reviewer (parent_patient).
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope per recensioni approvate
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
