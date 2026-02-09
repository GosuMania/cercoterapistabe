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
     * Get the user that saved this user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the saved user.
     */
    public function savedUser()
    {
        return $this->belongsTo(User::class, 'saved_user_id');
    }
}
