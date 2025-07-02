<details open>
<summary>🇬🇧 English Version</summary>

# ReservationController – API Reference

This document provides detailed reference for all **public API endpoints** in the `ReservationController`, including example requests and responses.

---

## `GET /api/reservations`

**Description:** Retrieve all reservations of the authenticated user.

**Example request:**
```http
GET /api/reservations HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Example response:**
```json
[
  {
    "id": 1,
    "status": "active",
    "start_datetime": "2025-07-03T08:00:00",
    "end_datetime": "2025-07-03T12:00:00",
    "parking_spots": [...],
    "license_plates": ["AB-123-CD"],
    "total_cost": 12.00,
    "created_at": "2025-07-01T10:00:00"
  }
]
```

---

## `GET /api/reservations/{id}`

**Description:** Show a specific reservation.

**Example request:**
```http
GET /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Example response:**
```json
{
  "id": 1,
  "status": "active",
  "summary": {...}
}
```

---

## `POST /api/reservations`

**Description:** Create a new reservation.

**Example request:**
```http
POST /api/reservations HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "parking_id": 1,
  "spot_identifiers": ["A1"],
  "license_plates": ["AB-123-CD"],
  "start_datetime": "2025-07-04T08:00:00",
  "end_datetime": "2025-07-04T12:00:00"
}
```

**Example response:**
```json
{
  "message": "Reservation created successfully.",
  "reservation": {
    "id": 12,
    "status": "active",
    "total_cost": 20.00
  }
}
```

---

## `PUT /api/reservations/{id}`

**Description:** Update a reservation.

**Example request:**
```http
PUT /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "start_datetime": "2025-07-04T09:00:00",
  "end_datetime": "2025-07-04T13:00:00"
}
```

**Example response:**
```json
{
  "message": "Reservation updated successfully.",
  "reservation": {...}
}
```

---

## `DELETE /api/reservations/{id}`

**Description:** Cancel a reservation.

**Example request:**
```http
DELETE /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
```

**Example response:**
```json
{
  "message": "Reservation cancelled successfully."
}
```

---

## `POST /api/reservations/calculate`

**Description:** Estimate the cost and duration of a reservation before confirming it.

**Example request:**
```http
POST /api/reservations/calculate HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "parking_id": 1,
  "spot_identifiers": ["A1"],
  "start_datetime": "2025-07-04T08:00:00",
  "end_datetime": "2025-07-04T12:00:00"
}
```

**Example response:**
```json
{
  "total_cost": 20.00,
  "duration_hours": 4,
  "per_hour_rate": 5.00
}
```

</details>

<details>
<summary>🇫🇷 Version française</summary>

# ReservationController – Référence de l’API

Ce document fournit une référence détaillée pour tous les **points de terminaison publics** de l’API du `ReservationController`, avec exemples de requêtes et réponses.

---

## `GET /api/reservations`

**Description :** Récupère toutes les réservations de l’utilisateur connecté.

**Exemple de requête :**
```http
GET /api/reservations HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Exemple de réponse :**
```json
[
  {
    "id": 1,
    "status": "active",
    "start_datetime": "2025-07-03T08:00:00",
    "end_datetime": "2025-07-03T12:00:00",
    "parking_spots": [...],
    "license_plates": ["AB-123-CD"],
    "total_cost": 12.00,
    "created_at": "2025-07-01T10:00:00"
  }
]
```

---

## `GET /api/reservations/{id}`

**Description :** Affiche une réservation spécifique.

**Exemple de requête :**
```http
GET /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Exemple de réponse :**
```json
{
  "id": 1,
  "status": "active",
  "summary": {...}
}
```

---

## `POST /api/reservations`

**Description :** Crée une nouvelle réservation.

**Exemple de requête :**
```http
POST /api/reservations HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "parking_id": 1,
  "spot_identifiers": ["A1"],
  "license_plates": ["AB-123-CD"],
  "start_datetime": "2025-07-04T08:00:00",
  "end_datetime": "2025-07-04T12:00:00"
}
```

**Exemple de réponse :**
```json
{
  "message": "Réservation créée avec succès.",
  "reservation": {
    "id": 12,
    "status": "active",
    "total_cost": 20.00
  }
}
```

---

## `PUT /api/reservations/{id}`

**Description :** Met à jour une réservation.

**Exemple de requête :**
```http
PUT /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "start_datetime": "2025-07-04T09:00:00",
  "end_datetime": "2025-07-04T13:00:00"
}
```

**Exemple de réponse :**
```json
{
  "message": "Réservation mise à jour avec succès.",
  "reservation": {...}
}
```

---

## `DELETE /api/reservations/{id}`

**Description :** Annule une réservation.

**Exemple de requête :**
```http
DELETE /api/reservations/1 HTTP/1.1
Authorization: Bearer {token}
```

**Exemple de réponse :**
```json
{
  "message": "Réservation annulée avec succès."
}
```

---

## `POST /api/reservations/calculate`

**Description :** Estime le coût et la durée d’une réservation avant confirmation.

**Exemple de requête :**
```http
POST /api/reservations/calculate HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "parking_id": 1,
  "spot_identifiers": ["A1"],
  "start_datetime": "2025-07-04T08:00:00",
  "end_datetime": "2025-07-04T12:00:00"
}
```

**Exemple de réponse :**
```json
{
  "total_cost": 20.00,
  "duration_hours": 4,
  "per_hour_rate": 5.00
}
```

</details>
