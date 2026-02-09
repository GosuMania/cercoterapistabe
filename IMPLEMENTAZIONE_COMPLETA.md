# Implementazione Completa - Cerco Terapista

## ✅ Funzionalità Implementate

### 1. Sistema di Recensioni e Valutazioni
- ✅ Migration `create_reviews_table`
- ✅ Model `Review` con relazioni morphTo
- ✅ Controller `ReviewController` con:
  - `index()` - Lista recensioni di un utente
  - `store()` - Crea recensione (solo genitori)
  - `show()` - Mostra recensione
  - `respond()` - Risposta a recensione
  - `report()` - Segnala recensione
  - `destroy()` - Elimina recensione
- ✅ Resource `ReviewResource`
- ✅ Calcolo valutazione media nei modelli `TherapistProfile` e `CenterProfile`

### 2. Modulo Annunci
- ✅ Migration `create_announcements_table`
- ✅ Model `Announcement` con scope (active, recruiting, promotional)
- ✅ Controller `AnnouncementController` con:
  - `index()` - Lista annunci con filtri
  - `recruiting()` - Annunci recruiting per terapisti
  - `store()` - Crea annuncio (solo centri)
  - `show()` - Mostra annuncio
  - `update()` - Aggiorna annuncio
  - `destroy()` - Elimina annuncio
  - `notifyTherapists()` - Notifiche push per annunci recruiting
- ✅ Resource `AnnouncementResource`

### 3. Onboarding Obbligatorio
- ✅ Campo `onboarding_completed` in tabella `users`
- ✅ Middleware `CheckOnboarding` per bloccare accesso
- ✅ Metodi in `UserController`:
  - `getOnboardingStatus()` - Verifica stato onboarding
  - `completeOnboarding()` - Completa onboarding
  - `checkOnboardingComplete()` - Validazione campi obbligatori
  - `getMissingFields()` - Lista campi mancanti
- ✅ Validazione campi obbligatori per tipo utente:
  - **Genitore**: Nome, Cognome, Email, Therapies, Location
  - **Terapista**: Nome, Cognome, Email, Profession, Therapies, Hourly Rate, Location
  - **Centro**: Nome, Cognome, Email, Center Name, Partita IVA, Service, Location

### 4. Ricerca con Geolocalizzazione
- ✅ Aggiornato `UserController::search()` con:
  - Filtri geolocalizzazione (latitude, longitude, radius)
  - Calcolo distanza con formula Haversine
  - Ordinamento risultati per distanza
  - Filtro per raggio geografico
- ✅ Integrazione con tabella `locations`
- ✅ Distanza inclusa nei risultati (`distance` in km)

### 5. Filtri di Ricerca Completi
- ✅ Filtri aggiuntivi in `UserController::search()`:
  - `home_therapy` - Disponibilità domiciliare
  - `min_hourly_rate` / `max_hourly_rate` - Range prezzo
  - `min_rating` - Valutazione minima
  - `min_years_experience` - Anni di esperienza
  - `contract_type` - Tipo contratto (per ricerca centri con posizioni aperte)

### 6. Allegati nei Messaggi
- ✅ Migration `create_message_attachments_table`
- ✅ Model `MessageAttachment`
- ✅ Supporto upload file (PDF e immagini) in `MessageController::store()`
- ✅ Storage file in `storage/app/public/message_attachments`
- ✅ Resource `MessageAttachmentResource` con URL file
- ✅ Validazione: max 5 file, max 10MB per file

### 7. Stati dei Messaggi
- ✅ Campi aggiunti a `messages`:
  - `sent_at` - Timestamp invio
  - `delivered_at` - Timestamp consegna
  - `read_at` - Timestamp lettura
- ✅ Metodo `markAsRead()` in `MessageController`
- ✅ Aggiornamento automatico stati
- ✅ Stati inclusi in `MessageResource`

### 8. Filtri Anti-Spam
- ✅ Migration `create_user_interactions_table`
- ✅ Model `UserInteraction`
- ✅ Validazione in `ConversationController::store()`:
  - I Centri possono contattare Genitori solo se il Genitore ha interagito prima
- ✅ Tracciamento interazioni in `UserController::show()`:
  - Visualizzazione profilo registrata come interazione

### 9. Campi Mancanti nei Profili
- ✅ **CenterProfile**:
  - `partita_iva` (obbligatorio)
  - `logo_url` (opzionale)
- ✅ **TherapistProfile**:
  - `affiliation_center_id` (relazione con centro)
  - `years_of_experience` (anni esperienza)
- ✅ Aggiornati migrations, models e `UserController::updateProfile()`

### 10. API Location Management
- ✅ Controller `LocationController` completo:
  - `index()` - Lista location utente
  - `store()` - Crea location
  - `show()` - Mostra location
  - `update()` - Aggiorna location
  - `destroy()` - Elimina location
- ✅ Resource `LocationResource`
- ✅ Gestione location di default

### 11. Routes Aggiornate
- ✅ Tutte le nuove API registrate in `routes/api.php`
- ✅ Middleware `onboarding` applicato alle route protette
- ✅ Route onboarding esenti dal controllo

### 12. Resources Aggiornate
- ✅ `UserResource` - Aggiunti: `onboardingCompleted`, `locations`, `distance`
- ✅ `TherapistProfileResource` - Aggiunti: `affiliationCenter`, `yearsOfExperience`, `averageRating`
- ✅ `CenterProfileResource` - Aggiunti: `partitaIva`, `logoUrl`, `averageRating`, `announcements`
- ✅ `MessageResource` - Aggiunti: `attachments`, `sentAt`, `deliveredAt`, `readAt`

## 📋 Struttura Database

### Nuove Tabelle
1. **reviews** - Recensioni e valutazioni
2. **announcements** - Annunci recruiting e promozionali
3. **message_attachments** - Allegati messaggi
4. **user_interactions** - Tracciamento interazioni utenti

### Tabelle Modificate
1. **users** - Aggiunto `onboarding_completed`
2. **center_profiles** - Aggiunti `partita_iva`, `logo_url`
3. **therapist_profiles** - Aggiunti `affiliation_center_id`, `years_of_experience`
4. **messages** - Aggiunti `sent_at`, `delivered_at`, `read_at`

## 🔐 Sicurezza e Validazioni

- ✅ Solo genitori possono recensire
- ✅ Solo centri possono creare annunci
- ✅ Filtri anti-spam per messaggistica
- ✅ Validazione permessi basata su ruoli
- ✅ Onboarding obbligatorio per accesso funzionalità

## 📡 API Endpoints

### Recensioni
- `GET /api/reviews` - Lista recensioni
- `POST /api/reviews` - Crea recensione
- `GET /api/reviews/{id}` - Mostra recensione
- `POST /api/reviews/{id}/respond` - Risposta a recensione
- `POST /api/reviews/{id}/report` - Segnala recensione
- `DELETE /api/reviews/{id}` - Elimina recensione

### Annunci
- `GET /api/announcements` - Lista annunci
- `GET /api/announcements/recruiting` - Annunci recruiting
- `POST /api/announcements` - Crea annuncio
- `GET /api/announcements/{id}` - Mostra annuncio
- `PUT /api/announcements/{id}` - Aggiorna annuncio
- `DELETE /api/announcements/{id}` - Elimina annuncio

### Location
- `GET /api/locations` - Lista location
- `POST /api/locations` - Crea location
- `GET /api/locations/{id}` - Mostra location
- `PUT /api/locations/{id}` - Aggiorna location
- `DELETE /api/locations/{id}` - Elimina location

### Onboarding
- `GET /api/user/onboarding-status` - Stato onboarding
- `POST /api/user/complete-onboarding` - Completa onboarding

### Messaggi
- `POST /api/messages/{id}/mark-read` - Marca come letto

## 🚀 Prossimi Passi

1. **Eseguire le migrations**:
   ```bash
   php artisan migrate
   ```

2. **Creare symlink per storage**:
   ```bash
   php artisan storage:link
   ```

3. **Testare le API** con Postman/Swagger

4. **Configurare Firebase** per notifiche push

5. **Testare onboarding** con utenti di test

## 📝 Note

- Le notifiche push per annunci recruiting inviano a tutti i terapisti (potrebbe essere ottimizzato con filtri geografici/competenze)
- Il calcolo della distanza usa la formula Haversine (precisa per distanze brevi)
- Il filtro per rating viene applicato in memoria (potrebbe essere ottimizzato con query SQL)
- Gli allegati vengono salvati in `storage/app/public/message_attachments`
