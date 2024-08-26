<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentPatientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'relationship',
        'patient_name',
        'patient_birthdate',
    ];

    protected $dates = [
        'patient_birthdate',
    ];

    /**
     * Get the user that owns the parent-patient profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
