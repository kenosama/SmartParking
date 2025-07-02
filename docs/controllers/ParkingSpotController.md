

<details open>
<summary>🇬🇧 English Version</summary>

# 🧠 ParkingSpotController – Internal Logic

This controller manages parking spots within a parking. All routes require authentication.

---

## 📘 `index(Parking $parking)`

### Role
Lists all spots for a given parking.

### Access Control
- Only the parking's **creator**, **co-owner**, or an **admin**.

### Logic
- Returns all non-deleted spots for the parking.

---

## 📘 `show(ParkingSpot $spot)`

### Role
Shows detailed info for a single parking spot.

### Access Control
- Only the **creator**, a **co-owner** of the parent parking, or an **admin**.

### Logic
- Returns formatted spot via resource.

---

## 📘 `store(Request $request, Parking $parking)`

### Role
Adds one or more new parking spots.

### Access Control
- Only the **creator** of the parking or an **admin**.

### Logic
- Validates shorthand like `"A1-A5,B1-B3"`.
- Parses it into individual codes.
- Creates `ParkingSpot` records for each.
- Assigns them to the parking.

---

## 📘 `update(Request $request, ParkingSpot $spot)`

### Role
Updates one parking spot.

### Access Control
- Only the **creator** of the parking or an **admin**.

### Logic
- Validates `is_available`, `is_active`.
- Updates spot fields.

---

## 📘 `destroy(ParkingSpot $spot)`

### Role
Soft-deletes a parking spot.

### Access Control
- Only the **creator** or an **admin**.

### Logic
- Sets `is_active = false`.

---

## 📘 `setAvailability(Request $request, ParkingSpot $spot)`

### Role
Explicitly sets `is_available` status for a spot.

### Access Control
- Only the **creator** or **admin**.

### Logic
- Validates boolean value.
- Updates spot’s `is_available` flag.

---

## 🛠️ Private Helper

### `parseSpotNames(string $input)`

- Translates `"A1-A3,B1,B2"` into individual spot codes.
- Supports ranges and comma-separated lists.

---

### `isCapacityExceeded(Parking $parking, int $newCount): bool`

- Returns `true` if the number of existing spots plus new ones exceeds the parking's total capacity.

---

### `getDuplicateIdentifiers(Parking $parking, array $identifiers): array`

- Returns a list of uppercased identifiers that already exist in the specified parking.

---

### `isUserAuthorizedForParking(Parking $parking): bool`

- Checks if the current user is the parking's owner, co-owner, or admin.

---

### `getSpotValidationRules(bool $isCreate = false): array`

- Returns validation rules used for `store` and `update` methods, adapting the rules based on whether it's a creation or an update.

---

### `formatSpotResponse($spots)`

- Formats a list of parking spots grouped by parking and owner.
- Used to return structured responses from advanced spot search.


---

## 📘 `search(Request $request)`

### Role
Searches for available parking spots using different filters.

### Access Control
- Public route – no authentication required.

### Logic
- If `country` is provided → return list of zip codes for that country.
- If `zip_code` is provided → return all active parkings in that zip code with their available spots.
- If `parking_id` is provided → return detailed spot info for the selected parking.
- If no filters are provided → returns 400 with an error message.
- Results exclude already reserved spots within the given time range.

### Filters supported
- `country`
- `zip_code`
- `parking_id`
- `start_datetime`
- `end_datetime`

</details>

<details>
<summary>🇫🇷 Version française</summary>

# 🧠 ParkingSpotController – Logique interne

Ce contrôleur gère les places de stationnement. Toutes les routes sont sécurisées par `auth:sanctum`.

---

## 📘 `index(Parking $parking)`

### Rôle
Liste toutes les places d’un parking donné.

### Contrôle d’accès
- Créateur du parking, co-propriétaire ou admin.

### Logique
- Retourne les places actives (non supprimées).

---

## 📘 `show(ParkingSpot $spot)`

### Rôle
Affiche les détails d’une place de parking.

### Contrôle d’accès
- Créateur, co-propriétaire ou admin.

### Logique
- Retourne la place via une ressource dédiée.

---

## 📘 `store(Request $request, Parking $parking)`

### Rôle
Ajoute une ou plusieurs places.

### Contrôle d’accès
- Seul le créateur ou un admin.

### Logique
- Valide une chaîne comme `"A1-A3,B1-B3"`.
- La transforme en plusieurs codes.
- Crée un `ParkingSpot` pour chaque code.

---

## 📘 `update(Request $request, ParkingSpot $spot)`

### Rôle
Met à jour une place existante.

### Contrôle d’accès
- Seul le créateur ou un admin.

### Logique
- Valide `is_available` et `is_active`.
- Met à jour les champs.

---

## 📘 `destroy(ParkingSpot $spot)`

### Rôle
Supprime une place (soft delete).

### Contrôle d’accès
- Seul le créateur ou un admin.

### Logique
- Définit `is_active = false`.

---

## 📘 `setAvailability(Request $request, ParkingSpot $spot)`

### Rôle
Modifie explicitement l’état de disponibilité (`is_available`).

### Contrôle d’accès
- Seul le créateur ou un admin.

### Logique
- Valide une valeur booléenne.
- Met à jour le champ.

---

## 🛠️ Fonction privée

### `parseSpotNames(string $input)`

- Convertit `"A1-A3,B1,B2"` en liste de codes individuels.
- Supporte les plages (`A1-A3`) et les listes (`B1,B2`).

---

### `isCapacityExceeded(Parking $parking, int $newCount): bool`

- Retourne `true` si le nombre total de places (existantes + nouvelles) dépasse la capacité maximale du parking.

---

### `getDuplicateIdentifiers(Parking $parking, array $identifiers): array`

- Retourne les identifiants (en majuscules) déjà existants dans le parking donné.

---

### `isUserAuthorizedForParking(Parking $parking): bool`

- Vérifie si l’utilisateur actuel est le créateur, co-propriétaire ou admin.

---

### `getSpotValidationRules(bool $isCreate = false): array`

- Retourne les règles de validation utilisées dans `store` et `update`, selon s’il s’agit d’une création ou d’une mise à jour.

---

### `formatSpotResponse($spots)`

- Formate une liste de places groupées par parking et par propriétaire.
- Utilisé pour les réponses structurées dans les recherches complexes de places.


---

## 📘 `search(Request $request)`

### Rôle
Recherche les places de stationnement disponibles selon plusieurs filtres.

### Contrôle d’accès
- Route publique – aucune authentification nécessaire.

### Logique
- Si `country` est fourni → retourne les codes postaux associés.
- Si `zip_code` est fourni → retourne tous les parkings actifs avec leurs places disponibles.
- Si `parking_id` est fourni → retourne le détail des places disponibles de ce parking.
- Si aucun filtre n’est fourni → erreur 400.

### Filtres supportés
- `country`
- `zip_code`
- `parking_id`
- `start_datetime`
- `end_datetime`

</details>