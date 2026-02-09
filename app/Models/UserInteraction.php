<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'viewer_id',
        'viewed_id',
        'interaction_type',
    ];

    /**
     * Get the user who made the interaction.
     */
    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * Get the user who was viewed/interacted with.
     */
    public function viewed()
    {
        return $this->belongsTo(User::class, 'viewed_id');
    }

    /**
     * Scope per interazioni di un utente specifico
     */
    public function scopeForViewed($query, $userId)
    {
        return $query->where('viewed_id', $userId);
    }

    /**
     * Scope per tipo di interazione
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('interaction_type', $type);
    }
}
