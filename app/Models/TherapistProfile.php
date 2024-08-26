<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TherapistProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profession',
        'specialization',
        'bio',
    ];

    /**
     * Get the user that owns the therapist profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
