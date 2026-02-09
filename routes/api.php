<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TherapistCenterRelationshipController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LocationController;

// ============================================================================
// AUTENTICAZIONE
// ============================================================================
Route::controller(AuthController::class)->prefix('auth')->group(function () {
    // Login o registrazione (supporta Firebase e email/password)
    Route::post('login-or-register', 'loginOrRegister');
    
    // Logout utente autenticato
    Route::post('logout', 'logout')->middleware('auth:sanctum');
    
    // Aggiorna nome e cognome per utenti social (deprecato, usare update-profile)
    Route::post('update-name-surname-social', 'updateNameSurnameSocial')->middleware('auth:sanctum');
    
    // Aggiorna dati utente e profilo (alternativa a user/update-profile)
    Route::put('update', 'update')->middleware('auth:sanctum');
});

// ============================================================================
// ONBOARDING E PROFILO UTENTE (non richiedono onboarding completato)
// ============================================================================
Route::controller(UserController::class)->prefix('user')->middleware('auth:sanctum')->group(function () {
    // Ottiene informazioni dell'utente autenticato
    Route::get('get-info-user', 'getInfoUser');
    
    // Aggiorna profilo utente (nome, cognome, tipo, profilo specifico)
    Route::post('update-profile', 'updateProfile');
    
    // Verifica stato onboarding (campi obbligatori completati)
    Route::get('onboarding-status', 'getOnboardingStatus');
    
    // Completa onboarding e verifica tutti i campi obbligatori
    Route::post('complete-onboarding', 'completeOnboarding');
});

// ============================================================================
// ROUTE PROTETTE (richiedono onboarding completato)
// ============================================================================
Route::middleware(['auth:sanctum', 'onboarding'])->group(function () {
    
    // ========================================================================
    // GESTIONE UTENTI
    // ========================================================================
    Route::controller(UserController::class)->prefix('user')->group(function () {
        // Lista tutti gli utenti (con filtri opzionali)
        Route::get('get-all-users', 'index');
        
        // Lista utenti salvati/preferiti dall'utente autenticato
        Route::get('get-saved-users', 'getSavedUsers');
        
        // Salva/rimuove utente dai preferiti (toggle)
        Route::post('toggle-saved-user', 'toggleSavedUser');
        
        // Ricerca utenti con filtri avanzati (geolocalizzazione, terapie, prezzo, rating, etc.)
        Route::post('search', 'search');
        
        // Dettaglio utente specifico (traccia interazione per anti-spam)
        Route::get('{id}', 'show');
        
        // Elimina utente (solo per admin o utente stesso)
        Route::delete('{id}', 'destroy');
    });

    // ========================================================================
    // CONVERSAZIONI E MESSAGGI
    // ========================================================================
    Route::controller(ConversationController::class)
        ->prefix('conversations')
        ->group(function () {
            // Lista tutte le conversazioni (admin)
            Route::get('/', 'index');
            
            // Lista conversazioni dell'utente autenticato (deve venire prima di {id})
            Route::get('/user', 'getByAuthenticatedUser');
            
            // Crea nuova conversazione o recupera esistente (con controllo anti-spam)
            Route::post('/', 'store');
            
            // Dettaglio conversazione con messaggi paginati
            Route::get('{id}', 'show');
            
            // Aggiorna nome conversazione
            Route::put('{id}', 'update');
            
            // Elimina conversazione
            Route::delete('{id}', 'destroy');
        });

    Route::controller(MessageController::class)
        ->prefix('messages')
        ->group(function () {
            // Lista messaggi di una conversazione (paginati) - deve venire prima di {id}
            Route::get('conversation/{conversationId}', 'index');
            
            // Invia nuovo messaggio (con supporto allegati PDF/immagini) - deve venire prima di {id}
            Route::post('conversation/{conversationId}', 'store');
            
            // Marca messaggio come letto (aggiorna read_at) - deve venire prima di {id}
            Route::post('{id}/mark-read', 'markAsRead');
            
            // Dettaglio messaggio specifico
            Route::get('{id}', 'show');
            
            // Aggiorna contenuto messaggio (solo mittente)
            Route::put('{id}', 'update');
            
            // Elimina messaggio (solo mittente)
            Route::delete('{id}', 'destroy');
        });

    // ========================================================================
    // RELAZIONI TERAPISTA-CENTRO
    // ========================================================================
    Route::controller(TherapistCenterRelationshipController::class)
        ->prefix('relationships')
        ->group(function () {
            // Lista tutte le relazioni terapista-centro
            Route::get('/', 'index');
            
            // Crea nuova relazione (collaborazione tra terapista e centro)
            Route::post('/', 'store');
            
            // Aggiorna stato relazione (Pending/Accepted/Declined)
            Route::put('{id}', 'update');
        });

    // ========================================================================
    // RECENSIONI E VALUTAZIONI
    // ========================================================================
    Route::controller(ReviewController::class)
        ->prefix('reviews')
        ->group(function () {
            // Lista recensioni di un utente (therapist o center) - richiede user_id
            Route::get('/', 'index');
            
            // Crea nuova recensione (solo genitori possono recensire)
            Route::post('/', 'store');
            
            // Dettaglio recensione specifica
            Route::get('{id}', 'show');
            
            // Risposta a recensione (solo terapista/centro recensito)
            Route::post('{id}/respond', 'respond');
            
            // Segnala recensione per moderazione (solo recensito)
            Route::post('{id}/report', 'report');
            
            // Elimina recensione (solo recensore)
            Route::delete('{id}', 'destroy');
        });

    // ========================================================================
    // ANNUNCI (Recruiting e Promozionali)
    // ========================================================================
    Route::controller(AnnouncementController::class)
        ->prefix('announcements')
        ->group(function () {
            // Lista annunci attivi con filtri (tipo, centro, contratto)
            Route::get('/', 'index');
            
            // Lista annunci recruiting per terapisti (con notifiche push) - deve venire prima di {id}
            Route::get('recruiting', 'recruiting');
            
            // Crea nuovo annuncio (solo centri)
            Route::post('/', 'store');
            
            // Dettaglio annuncio specifico
            Route::get('{id}', 'show');
            
            // Aggiorna annuncio (solo centro proprietario)
            Route::put('{id}', 'update');
            
            // Elimina annuncio (solo centro proprietario)
            Route::delete('{id}', 'destroy');
        });

    // ========================================================================
    // GESTIONE LOCATION (Geolocalizzazione)
    // ========================================================================
    Route::controller(LocationController::class)
        ->prefix('locations')
        ->group(function () {
            // Lista location dell'utente autenticato
            Route::get('/', 'index');
            
            // Crea nuova location (con coordinate GPS)
            Route::post('/', 'store');
            
            // Dettaglio location specifica
            Route::get('{id}', 'show');
            
            // Aggiorna location (coordinate, indirizzo, default)
            Route::put('{id}', 'update');
            
            // Elimina location
            Route::delete('{id}', 'destroy');
        });
});
