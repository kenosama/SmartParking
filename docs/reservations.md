# 📑 API Documentation — Reservations

Ce fichier documente les différentes requêtes pour gérer les réservations dans l’application SmartParking.

---

## 🔐 Authentification requise
Toutes les routes ci-dessous nécessitent que l’utilisateur soit authentifié via un token (Bearer).

---

## 📘 GET /api/reservations

Récupère la liste des réservations de l’utilisateur connecté (ou admin pour tout voir).

**Réponse :**
- 200 OK
- Liste des réservations avec détails du spot, parking et utilisateur.

---

## 📘 GET /api/reservations/{reservation}

Affiche les détails d’une réservation spécifique.

**Paramètres :**
- `reservation` (int) — ID de la réservation.

**Réponse :**
- 200 OK avec les données de la réservation.
- 403 si l’utilisateur n’a pas le droit de la voir.

---

## ✏️ POST /api/reservations

Crée une nouvelle réservation.

**Champs requis :**
- `parking_id` (int)
- `spot_identifiers` (string) — Ex: `"A1,A2,B1"`
- `license_plates` (string) — Ex: `"1-WUL-166,DB 543 ASER"`
- `start_datetime` (datetime)
- `end_datetime` (datetime)

**Comportement :**
- Nettoie les plaques (suppression symboles/espaces).
- Vérifie la disponibilité des places pour le créneau choisi.
- Crée une réservation pour chaque spot.

**Réponse :**
- 201 Created avec les réservations.
- 400 ou 409 en cas d’erreur ou conflit.

---

## ✏️ PUT /api/reservations/{reservation}

Met à jour une réservation existante.

**Paramètres :**
- `reservation` (int)

**Champs possibles :**
- `start_datetime`, `end_datetime`
- `license_plates`
- `spot_identifiers`

**Réponse :**
- 200 OK avec les données mises à jour.
- 403 si l’utilisateur ne peut pas modifier cette réservation.

---

## 🗑️ DELETE /api/reservations/{reservation}

Annule (soft delete) une réservation :

**Règles :**
- Admin : peut annuler à tout moment → statut : `cancelled_by_admin`
- Propriétaire du spot : peut annuler si plus de 48h → statut : `cancelled_by_owner`
- Utilisateur ayant réservé : peut annuler si plus de 24h → statut : `cancelled_by_user`

**Réponse :**
- 200 OK avec message de confirmation.
- 403 si annulation non autorisée.

---

## ⏱️ Cron automatique (à venir)

- Transition automatique des statuts `active` → `done` une fois l’heure de fin atteinte.

---

