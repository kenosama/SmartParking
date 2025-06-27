

# ParkingSpotController
<detail open> 
<summary>🇬🇧 English Version</summary>
> [EN] API Controller for managing parking spots.

---

## Authentication

All routes are protected by Sanctum.  
The user must be authenticated, and their roles (`is_admin`, `is_owner`, `is_tenant`) are used to restrict access.

---

This controller handles the following operations:

- List accessible spots (`index`)
- Create one or more spots (`store`)
- View the details of a spot (`show`)
- Update a spot (`update`)
- Deactivate a spot (`destroy`)
- Search for available spots (`search`)

---

## Methods

### `index()`

- **Method:** `GET /api/parking-spots`
- **Description:** Returns all parking spots accessible by the authenticated user.
- **Access:** admin, owner or co-owner
- **Response:** Grouped list by parking, with grouping by owner.
- **Format:** Uses `formatSpotResponse()`

---

### `store(Request $request)`

- **Method:** `POST /api/parking-spots`
- **Description:** Creates one or more spots based on a `identifiers` string with ranges.
- **Validation:**
  - `identifiers`: required string (e.g. `"A1-A5,B1,B2-B3"`)
  - `parking_id`: existing parking ID
  - `allow_electric_charge`, `is_available`, `per_day_only`: booleans
  - `price_per_day`, `price_per_hour`: numeric values ≥ 0
  - `note`: optional string
- **Additional checks:**
  - Capacity not exceeded
  - Duplicate identifiers rejected
- **Response:** Newly created spots in standard format (`formatSpotResponse`)

---

### `show(ParkingSpot $parkingSpot)`

- **Method:** `GET /api/parking-spots/{id}`
- **Description:** Displays a spot’s detail, with parking and owner info.
- **Response:** Single `formatSpotResponse` for one spot

---

### `update(Request $request, ParkingSpot $parkingSpot)`

- **Method:** `PATCH /api/parking-spots/{id}`
- **Description:** Updates the spot's information.
- **Access:** spot owner, parking owner, or admin.
- **Updatable fields:**
  - `identifier`: validated against duplicates in the same parking
  - `user_id`: can be changed by admin or parking owner (also updates the pivot table)
- **Response:** Updated spot in `formatSpotResponse` format.

---

### `destroy(ParkingSpot $parkingSpot)`

- **Method:** `DELETE /api/parking-spots/{id}`
- **Description:** Deactivates the spot (soft-delete = makes it unavailable).
- **Response:** JSON message `{ "message": "Spot deactivated." }`

---

### `search(Request $request)`

- **Method:** `GET /api/parking-spots/search`
- **Description:** Dynamic search:
  - `?country=BE` → returns available zip codes
  - `?zip_code=1050` → lists active parkings in the area + number of spots and price range
  - `?parking_id=7` → lists available spots for this parking, without owner info
- **Additional filtering:** possible with `start_datetime` and `end_datetime` to exclude already booked spots.
- **Response:** Adjusted according to the search parameter.

---

## Internal Methods

### `formatSpotResponse($spots)`
- Transforms a collection of `ParkingSpot` into grouped JSON format by parking and owner.

### `parseIdentifiers(string $input)`
- Converts a string like `"A1-A3,B1"` into a unique string collection (`["A1", "A2", "A3", "B1"]`)

### `getDuplicateIdentifiers()`
- Fetches already existing identifiers in a parking.

### `isCapacityExceeded()`
- Checks if the parking’s total capacity would be exceeded.

### `isUserAuthorizedForParking()`
- Checks if the current user has rights on a given parking.


</detail>
<detail>
<summary>🇫🇷 Vesion française</summary>
> [FR] Contrôleur API pour la gestion des emplacements de parking.  

---

## Authentification

Toutes les routes sont protégées par Sanctum.  
L’utilisateur doit être authentifié, et ses rôles (`is_admin`, `is_owner`, `is_tenant`) sont utilisés pour restreindre les accès.

---


Ce contrôleur permet d'effectuer les opérations suivantes :  
This controller handles the following operations:

- Lister les emplacements accessibles (`index`)
- Créer un ou plusieurs emplacements (`store`)
- Voir le détail d’un emplacement (`show`)
- Mettre à jour un emplacement (`update`)
- Désactiver un emplacement (`destroy`)
- Rechercher des emplacements disponibles (`search`)

---

## Méthodes

### `index()`

- **Méthode :** `GET /api/parking-spots`
- **Description :** Retourne tous les emplacements accessibles par l’utilisateur authentifié.
- **Accès :** admin, propriétaire ou co-propriétaire
- **Réponse :** Liste groupée par parking, avec regroupement par propriétaire.
- **Format :** Utilise `formatSpotResponse()`

---

### `store(Request $request)`

- **Méthode :** `POST /api/parking-spots`
- **Description :** Crée un ou plusieurs emplacements à partir d'une chaîne d’identifiants (`identifiers`) contenant des plages.
- **Validation :**
  - `identifiers`: chaîne obligatoire (e.g. `"A1-A5,B1,B2-B3"`)
  - `parking_id`: identifiant du parking existant
  - `allow_electric_charge`, `is_available`, `per_day_only` : booléens
  - `price_per_day`, `price_per_hour` : numériques >= 0
  - `note`: chaîne facultative
- **Vérifications supplémentaires :**
  - Capacité non dépassée
  - Identifiants dupliqués rejetés
- **Réponse :** Spots nouvellement créés au format standard (`formatSpotResponse`)

---

### `show(ParkingSpot $parkingSpot)`

- **Méthode :** `GET /api/parking-spots/{id}`
- **Description :** Affiche le détail d’un emplacement, avec info du parking et du propriétaire.
- **Réponse :** Unique `formatSpotResponse` sur un seul emplacement

---

### `update(Request $request, ParkingSpot $parkingSpot)`

- **Méthode :** `PATCH /api/parking-spots/{id}`
- **Description :** Met à jour les informations d’un spot.
- **Accès :** propriétaire de l’emplacement, propriétaire du parking ou admin.
- **Champs modifiables :**
  - `identifier` : validé contre les doublons dans le parking
  - `user_id` : changement possible par admin ou propriétaire du parking (met aussi à jour la table pivot)
- **Réponse :** Spot mis à jour, au format `formatSpotResponse`.

---

### `destroy(ParkingSpot $parkingSpot)`

- **Méthode :** `DELETE /api/parking-spots/{id}`
- **Description :** Désactive l’emplacement (soft-delete = rendu indisponible).
- **Réponse :** Message JSON `{ "message": "Spot deactivated." }`

---

### `search(Request $request)`

- **Méthode :** `GET /api/parking-spots/search`
- **Description :** Recherches dynamiques :
  - `?country=BE` → renvoie les codes postaux disponibles
  - `?zip_code=1050` → liste les parkings actifs dans la zone + nb de spots et fourchettes de prix
  - `?parking_id=7` → spots disponibles pour ce parking, sans info de propriétaires
- **Filtrage supplémentaire :** possible avec `start_datetime` et `end_datetime` pour éviter les spots déjà réservés.
- **Réponse :** Adaptée selon le paramètre passé.

---

## Méthodes internes

### `formatSpotResponse($spots)`
- Transforme une collection de `ParkingSpot` en format JSON groupé par parking et propriétaire.

### `parseIdentifiers(string $input)`
- Convertit une chaîne de type `"A1-A3,B1"` en collection unique de strings (`["A1", "A2", "A3", "B1"]`)

### `getDuplicateIdentifiers()`
- Récupère les identifiants déjà existants dans un parking.

### `isCapacityExceeded()`
- Vérifie si la capacité totale du parking serait dépassée.

### `isUserAuthorizedForParking()`
- Vérifie si l’utilisateur actuel a les droits sur un parking donné.
</detail>