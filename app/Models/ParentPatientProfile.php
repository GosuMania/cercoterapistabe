<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentPatientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'relationship',
        'therapies', // JSON
    ];

    protected $casts = [
        'therapies' => 'array', // Cast JSON in array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
