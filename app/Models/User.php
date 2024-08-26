<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Gli attributi che possono essere assegnati in modo massivo.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'is_premium',
        'firebase_token'
    ];

    /**
     * Gli attributi che dovrebbero essere nascosti per gli array.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Gli attributi che dovrebbero essere convertiti in tipi nativi.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relazione con il profilo del terapista.
     */
    public function therapistProfile()
    {
        return $this->hasOne(TherapistProfile::class);
    }

    /**
     * Relazione con il profilo del genitore/paziente.
     */
    public function parentPatientProfile()
    {
        return $this->hasOne(ParentPatientProfile::class);
    }

    /**
     * Relazione con il profilo del centro.
     */
    public function centerProfile()
    {
        return $this->hasOne(CenterProfile::class);
    }

    /**
     * Relazione con le conversazioni a cui l'utente partecipa.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class);
    }

    /**
     * Relazione con i messaggi inviati dall'utente.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
