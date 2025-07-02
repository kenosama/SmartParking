
<details open>
<summary>🇬🇧 English Version</summary>

# ReservationController – Internal Logic Documentation

This controller handles all reservation logic. Below is the full list of public and private methods.

---

## Public Methods

### `index()`
Lists all reservations with related data.

### `show(Reservation $reservation)`
Displays details of a specific reservation.

### `store(Request $request)`
Handles reservation creation logic (single, multiple, continuous), including validation and conflict detection.

### `update(Request $request, Reservation $reservation)`
Updates an existing reservation.

### `destroy(Reservation $reservation)`
Cancels a reservation and updates its status.

### `calculateCostAndDuration(Request $request)`
Estimates the duration and cost of a reservation based on the provided data.

---

## Private Methods

### `getValidationRules(bool $isCreate = true)`
Returns context-aware validation rules for reservation creation or update.

### `validateReservationDateLogic($startDateTime, $endDateTime)`
Checks that reservation dates are chronologically valid.

### `getUserIdAndFilterAuthorization($userId)`
Determines current user ID or validates admin request on behalf of others.

### `parseSpotsAndPlates($request)`
Extracts and maps input spot identifiers and license plates.

### `expandSpotIdentifiers($parkingId, $spotString)`
Expands a range-based identifier string (e.g. "A1-A3") into an array.

### `normalizeLicensePlates(string $plateString)`
Sanitizes and normalizes a list of vehicle license plates.

### `fetchParkingSpots(array $identifiers, int $parkingId)`
Retrieves and validates spot objects for given identifiers.

### `cancelPreviousReservations($spotIds, $start, $end)`
Marks conflicting active reservations as canceled.

### `isContinuousMode($request)`
Checks if reservation is of type "continuous" (multiple days/hours).

### `generateReservationSlots($spotIds, $plates, $start, $end, $isContinuous)`
Generates logical time/spot slots for multi-day or hourly continuous bookings.

### `explodeContinuousDateRange($start, $end)`
Splits a datetime range into individual continuous date-hour segments.

### `calculateCostAndDuration(...)`
Used internally to compute the total cost and time of reservation slots.

### `processReservationSlots($slots, $userId)`
Persists a list of generated reservation slots in the database.

### `buildReservationSummary($reservations)`
Generates a summary response for a reservation collection.

### `formatReservationResponse($reservation)`
Formats a single reservation for frontend consumption.

### `formatReservations($reservations)`
Formats a collection of reservations.

### `formatReservationsGroupedByStatus($reservations)`
Organizes a collection into active, expired, or canceled categories.

</details>

<details>
<summary>🇫🇷 Version française</summary>

# ReservationController – Documentation du fonctionnement interne

Ce contrôleur gère l’ensemble de la logique métier liée aux réservations. Voici la liste complète des méthodes.

---

## Méthodes publiques

### `index()`
Liste toutes les réservations avec leurs données associées.

### `show(Reservation $reservation)`
Affiche les détails d’une réservation spécifique.

### `store(Request $request)`
Gère la création de réservations simples, multiples ou continues. Inclut validation et détection des conflits.

### `update(Request $request, Reservation $reservation)`
Met à jour une réservation existante.

### `destroy(Reservation $reservation)`
Annule une réservation et met à jour son statut.

### `calculateCostAndDuration(Request $request)`
Estime la durée et le coût d’une réservation selon les données fournies.

---

## Méthodes privées

### `getValidationRules(bool $isCreate = true)`
Retourne les règles de validation adaptées au contexte (création ou modification).

### `validateReservationDateLogic($startDateTime, $endDateTime)`
Vérifie la validité chronologique des dates.

### `getUserIdAndFilterAuthorization($userId)`
Récupère l’ID de l’utilisateur courant ou vérifie si l’admin agit pour quelqu’un d’autre.

### `parseSpotsAndPlates($request)`
Analyse les identifiants de places et les plaques saisies.

### `expandSpotIdentifiers($parkingId, $spotString)`
Élargit une chaîne abrégée d’identifiants (ex: "A1-A3") en tableau.

### `normalizeLicensePlates(string $plateString)`
Nettoie et normalise les plaques de véhicule.

### `fetchParkingSpots(array $identifiers, int $parkingId)`
Récupère et valide les objets `ParkingSpot`.

### `cancelPreviousReservations($spotIds, $start, $end)`
Annule les réservations actives qui entrent en conflit.

### `isContinuousMode($request)`
Détecte si la réservation est de type continue (multi-jour ou horaire).

### `generateReservationSlots(...)`
Génère les créneaux horaires pour des réservations continues.

### `explodeContinuousDateRange($start, $end)`
Décompose une plage de dates/heures continues.

### `calculateCostAndDuration(...)`
Utilisée en interne pour estimer le coût et la durée.

### `processReservationSlots(...)`
Enregistre en base les créneaux générés.

### `buildReservationSummary($reservations)`
Génère un résumé d’une liste de réservations.

### `formatReservationResponse($reservation)`
Formate une réservation pour affichage frontend.

### `formatReservations($reservations)`
Formate une collection de réservations.

### `formatReservationsGroupedByStatus($reservations)`
Regroupe les réservations par statut : actives, expirées, annulées.

</details>
