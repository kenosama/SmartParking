

# ParkingSpotController

> [FR] Contrôleur API pour la gestion des emplacements de parking.  
> [EN] API Controller for managing parking spots.

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

---

## Authentification

Toutes les routes sont protégées par Sanctum.  
L’utilisateur doit être authentifié, et ses rôles (`is_admin`, `is_owner`, `is_tenant`) sont utilisés pour restreindre les accès.

---