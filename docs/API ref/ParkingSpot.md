

<details open>
<summary>🇬🇧 English Version</summary>

## ParkingSpotController – API Reference

---

### `GET /api/parkingspots`

- **Description:** List all parking spots (admin only).
- **Auth:** Required – Admin only.
- **Response:** JSON array of all spots with full metadata.

---

### `GET /api/parkingspots/{spot}`

- **Description:** Show details of a single spot.
- **Auth:** Required – Only the spot’s owner, co-owner of the parking, or admin.
- **Response:** JSON object of the spot.

---

### `POST /api/parkingspots`

- **Description:** Create one or multiple spots.
- **Auth:** Required – Must be the parking's creator or co-owner.
- **Body:**
```json
{
  "identifiers": "A1-A5,B1,B2",
  "parking_id": 1,
  "price_per_day": 15,
  "price_per_hour": 2.5,
  "per_day_only": false,
  "allow_electric_charge": true,
  "note": "Covered spots"
}
```
- **Response:** 201 Created with newly created spots.

---

### `PUT /api/parkingspots/{spot}`

- **Description:** Update a single spot.
- **Auth:** Required – Owner of the parking or admin.
- **Body:** Same structure as store, except `identifiers` is prohibited.
- **Response:** Updated spot JSON.

---

### `DELETE /api/parkingspots/{spot}`

- **Description:** Delete a parking spot.
- **Auth:** Required – Same as above.
- **Response:** 204 No Content.

---

### `PATCH /api/parkingspots/{spot}/availability`

- **Description:** Toggle spot availability.
- **Auth:** Required – Same as above.
- **Response:** JSON with `is_available` boolean.

---

### `GET /api/parkingspots/search`

- **Description:** Search for available parking spots using filters.
- **Auth:** Public.
- **Query Parameters:** `country`, `zip_code`, `parking_id`, `start_datetime`, `end_datetime`
- **Response:** JSON with grouped spots and pricing ranges.

</details>

<details>
<summary>🇫🇷 Version française</summary>

## ParkingSpotController – Référence API

---

### `GET /api/parkingspots`

- **Description :** Liste toutes les places (admin uniquement).
- **Auth :** Requise – Administrateur uniquement.
- **Réponse :** Tableau JSON contenant toutes les places avec métadonnées.

---

### `GET /api/parkingspots/{spot}`

- **Description :** Affiche les détails d’une place.
- **Auth :** Requise – Propriétaire de la place, co-propriétaire ou admin.
- **Réponse :** Objet JSON de la place.

---

### `POST /api/parkingspots`

- **Description :** Crée une ou plusieurs places.
- **Auth :** Requise – Créateur du parking ou co-propriétaire.
- **Body :**
```json
{
  "identifiers": "A1-A5,B1,B2",
  "parking_id": 1,
  "price_per_day": 15,
  "price_per_hour": 2.5,
  "per_day_only": false,
  "allow_electric_charge": true,
  "note": "Places couvertes"
}
```
- **Réponse :** 201 Created avec les nouvelles places.

---

### `PUT /api/parkingspots/{spot}`

- **Description :** Met à jour une place.
- **Auth :** Requise – Propriétaire ou admin.
- **Body :** Même structure que `store`, sauf `identifiers` interdit.
- **Réponse :** Objet JSON de la place mise à jour.

---

### `DELETE /api/parkingspots/{spot}`

- **Description :** Supprime une place.
- **Auth :** Requise – Idem ci-dessus.
- **Réponse :** 204 No Content.

---

### `PATCH /api/parkingspots/{spot}/availability`

- **Description :** Modifie la disponibilité de la place.
- **Auth :** Requise – Idem ci-dessus.
- **Réponse :** JSON avec le booléen `is_available`.

---

### `GET /api/parkingspots/search`

- **Description :** Recherche de places disponibles selon des filtres.
- **Auth :** Publique.
- **Paramètres :** `country`, `zip_code`, `parking_id`, `start_datetime`, `end_datetime`
- **Réponse :** JSON groupé avec les places et les tarifs.

</details>