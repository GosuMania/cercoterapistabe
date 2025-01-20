<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'morning',
        'afternoon',
        'evening',
    ];

    protected $casts = [
        'morning' => 'boolean',
        'afternoon' => 'boolean',
        'evening' => 'boolean',
    ];

    // Relazione con l'utente
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
