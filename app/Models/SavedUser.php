<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'saved_user_id',
    ];

    /**
     * Get the user that owns the therapist profile.
     */
    public function savedUser()
    {
        return $this->belongsTo(User::class, 'saved_user_id');
    }
}
