# 📑 API Documentation — Reservations

<details open>
<summary>🇬🇧 English</summary>

## 🔐 Authentication Required

All routes below require a valid Bearer token authentication.

---

## 📘 GET /api/reservations

Retrieve all reservations.

**Response:**
- 200 OK
- List of reservations with related user, parking and parking spot.

---

## 📘 GET /api/reservations/{reservation}

Retrieve a specific reservation.

**Parameters:**
- `reservation` (int) — Reservation ID

**Response:**
- 200 OK with reservation details
- 403 if unauthorized

---

## ✏️ POST /api/reservations

Create one or more reservations.

**Required fields:**
- `user_id` (int)
- `parking_id` (int)
- `parking_spot_identifiers` (string) — Example: `"A1,A2,B1-B3"`
- `reserved_date` (date) — `YYYY-MM-DD`
- `start_time` (string) — format `HH:MM`
- `end_time` (string) — must be after start_time
- `license_plate` (string) — Comma-separated, one per spot

**Behavior:**
- Accepts ranges like `B1-B3`
- Cleans license plates (removes symbols and spaces)
- Ensures parking is active
- Validates time slot availability per spot
- One reservation per spot and plate
- Rejects if end_time is before start_time (with validation)

**Response:**
- 201 Created with reservations
- 422 on validation error
- 409 on time conflict

---

## ✏️ PUT /api/reservations/{reservation}

Update an existing reservation.

**Parameters:**
- `reservation` (int)

**Optional fields:**
- `parking_id` (int)
- `parking_spot_identifiers` (string) — Example: `"A1,A2,B1-B3"`
- `reserved_date` (date) — `YYYY-MM-DD`
- `start_time` (string) — format `HH:MM`
- `end_time` (string) — must be after start_time
- `license_plate` (string) — Comma-separated, one per spot

**Behavior:**
- Accepts multiple spots and plates (like POST)
- Cleans license plates (removes symbols and spaces)
- Ensures the count of spots matches the number of license plates
- Prevents overlapping reservations for the same spots and time
- Validates and adjusts time if necessary (e.g. for per_day_only parkings)
- Rejects if end_time is before start_time (with validation)

**Response:**
- 200 OK with updated reservations
- 403 if unauthorized
- 409 on conflict (e.g. overlapping reservation)
- 422 on validation error

---

## 🗑️ DELETE /api/reservations/{reservation}

Soft delete (cancel) a reservation. The `status` field is updated instead of deleting.

**Rules:**
- Admin can cancel anytime → status `cancelled_by_admin`
- Parking owner can cancel if less than 48h before reservation → `cancelled_by_owner`
- User can cancel if more than 24h before → `cancelled_by_user`

**Response:**
- 200 OK with cancellation message and status
- 403 if not allowed

---

## ⏱️ Automatic status transition (Upcoming feature)

Reservations with `active` status will auto-update to `done` once the end time is reached.

</details>

---

<details>
<summary>🇫🇷 Français</summary>

## 🔐 Authentification requise

Toutes les routes ci-dessous nécessitent une authentification Bearer.

---

## 📘 GET /api/reservations

Récupère toutes les réservations.

**Réponse :**
- 200 OK
- Liste des réservations avec utilisateur, parking et emplacement.

---

## 📘 GET /api/reservations/{reservation}

Affiche une réservation spécifique.

**Paramètres :**
- `reservation` (int) — ID de la réservation

**Réponse :**
- 200 OK avec les détails
- 403 si accès refusé

---

## ✏️ POST /api/reservations

Crée une ou plusieurs réservations.

**Champs requis :**
- `user_id` (int)
- `parking_id` (int)
- `parking_spot_identifiers` (string) — Ex: `"A1,A2,B1-B3"`
- `reserved_date` (date)
- `start_time` (HH:MM)
- `end_time` (HH:MM)
- `license_plate` (string) — séparés par virgule

**Comportement :**
- Supporte les plages `B1-B3`
- Nettoie les plaques (espaces, symboles)
- Vérifie que le parking est actif
- Valide la disponibilité horaire
- Une réservation par emplacement
- Rejette si l’heure de fin est antérieure à l’heure de début (validation)

**Réponse :**
- 201 Created
- 422 si erreur de validation
- 409 si conflit d’horaire

---

## ✏️ PUT /api/reservations/{reservation}

Met à jour une réservation existante.

**Paramètres :**
- `reservation` (int)

**Champs possibles :**
- `parking_id` (int)
- `parking_spot_identifiers` (string) — ex: `"A1,A2,B1-B3"`
- `reserved_date` (date) — `YYYY-MM-DD`
- `start_time` (HH:MM)
- `end_time` (HH:MM) — doit être après start_time
- `license_plate` (string) — séparées par virgule, une par place

**Comportement :**
- Accepte plusieurs emplacements et plaques (comme POST)
- Nettoie les plaques (enlève symboles et espaces)
- Vérifie la cohérence entre le nombre d’emplacements et de plaques
- Empêche les réservations qui se chevauchent
- Valide et ajuste l’horaire si nécessaire (ex: pour les parkings à la journée uniquement)
- Rejette si l’heure de fin est antérieure à l’heure de début (validation)

**Réponse :**
- 200 OK avec les réservations mises à jour
- 403 si accès refusé
- 409 en cas de conflit (ex: réservation concurrente)
- 422 si erreur de validation

---

## 🗑️ DELETE /api/reservations/{reservation}

Annule une réservation (soft delete via champ `status`).

**Règles :**
- Admin → `cancelled_by_admin`
- Propriétaire du parking (si -48h) → `cancelled_by_owner`
- Utilisateur (si +24h) → `cancelled_by_user`

**Réponse :**
- 200 OK avec statut
- 403 si annulation interdite

---

## ⏱️ Transition automatique (à venir)

Les réservations actives passeront à `done` automatiquement à la fin.

</details>