<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'center_name',
        'service',
        'description',
    ];

    /**
     * Get the user that owns the center profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
