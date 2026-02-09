# Riepilogo Completo Endpoint API

## 📋 Endpoint Totali: 42

### 🔐 Autenticazione (4 endpoint)
- `POST /api/auth/login-or-register` - Login o registrazione (Firebase/email)
- `POST /api/auth/logout` - Logout utente
- `POST /api/auth/update-name-surname-social` - Aggiorna nome/cognome (deprecato)
- `PUT /api/auth/update` - Aggiorna dati utente (alternativa)

### 👤 Onboarding e Profilo (4 endpoint)
- `GET /api/user/get-info-user` - Info utente autenticato
- `POST /api/user/update-profile` - Aggiorna profilo completo
- `GET /api/user/onboarding-status` - Stato onboarding
- `POST /api/user/complete-onboarding` - Completa onboarding

### 👥 Gestione Utenti (6 endpoint)
- `GET /api/user/get-all-users` - Lista tutti gli utenti
- `GET /api/user/get-saved-users` - Utenti salvati/preferiti
- `POST /api/user/toggle-saved-user` - Salva/rimuovi preferito
- `POST /api/user/search` - Ricerca avanzata con geolocalizzazione
- `GET /api/user/{id}` - Dettaglio utente
- `DELETE /api/user/{id}` - Elimina utente

### 💬 Conversazioni (6 endpoint)
- `GET /api/conversations` - Lista tutte le conversazioni
- `GET /api/conversations/user` - Conversazioni utente autenticato
- `POST /api/conversations` - Crea/recupera conversazione
- `GET /api/conversations/{id}` - Dettaglio conversazione
- `PUT /api/conversations/{id}` - Aggiorna conversazione
- `DELETE /api/conversations/{id}` - Elimina conversazione

### 📨 Messaggi (6 endpoint)
- `GET /api/messages/conversation/{conversationId}` - Lista messaggi conversazione
- `POST /api/messages/conversation/{conversationId}` - Invia messaggio (con allegati)
- `POST /api/messages/{id}/mark-read` - Marca come letto
- `GET /api/messages/{id}` - Dettaglio messaggio
- `PUT /api/messages/{id}` - Aggiorna messaggio
- `DELETE /api/messages/{id}` - Elimina messaggio

### 🤝 Relazioni Terapista-Centro (3 endpoint)
- `GET /api/relationships` - Lista relazioni
- `POST /api/relationships` - Crea relazione
- `PUT /api/relationships/{id}` - Aggiorna stato relazione

### ⭐ Recensioni (6 endpoint)
- `GET /api/reviews` - Lista recensioni (richiede user_id)
- `POST /api/reviews` - Crea recensione (solo genitori)
- `GET /api/reviews/{id}` - Dettaglio recensione
- `POST /api/reviews/{id}/respond` - Risposta a recensione
- `POST /api/reviews/{id}/report` - Segnala recensione
- `DELETE /api/reviews/{id}` - Elimina recensione

### 📢 Annunci (6 endpoint)
- `GET /api/announcements` - Lista annunci con filtri
- `GET /api/announcements/recruiting` - Annunci recruiting per terapisti
- `POST /api/announcements` - Crea annuncio (solo centri)
- `GET /api/announcements/{id}` - Dettaglio annuncio
- `PUT /api/announcements/{id}` - Aggiorna annuncio
- `DELETE /api/announcements/{id}` - Elimina annuncio

### 📍 Location (5 endpoint)
- `GET /api/locations` - Lista location utente
- `POST /api/locations` - Crea location
- `GET /api/locations/{id}` - Dettaglio location
- `PUT /api/locations/{id}` - Aggiorna location
- `DELETE /api/locations/{id}` - Elimina location

---

## 🔒 Middleware Applicati

### Autenticazione Sanctum
- Tutte le route tranne `login-or-register` richiedono autenticazione

### Onboarding Middleware
- Route onboarding: esenti dal controllo
- Route protette: richiedono onboarding completato

---

## 📝 Note Importanti

1. **Ordine Route**: Le route specifiche (es. `/user`, `/recruiting`) devono venire prima di quelle con parametri dinamici (es. `{id}`)

2. **Validazioni**:
   - Solo genitori possono recensire
   - Solo centri possono creare annunci
   - Filtro anti-spam: centri possono contattare genitori solo dopo interazione

3. **Geolocalizzazione**: 
   - La ricerca supporta coordinate GPS e ordinamento per distanza
   - Parametri: `latitude`, `longitude`, `radius` (in metri)

4. **Allegati Messaggi**:
   - Supporto PDF e immagini
   - Max 5 file, max 10MB per file
   - Upload tramite multipart/form-data

---

## ✅ Verifica Completata

- ✅ Tutti i metodi pubblici dei controller hanno endpoint corrispondenti
- ✅ Tutti gli endpoint hanno commenti esplicativi
- ✅ Ordine route corretto (specifiche prima di dinamiche)
- ✅ Middleware applicati correttamente
- ✅ Nessun endpoint duplicato o mancante
