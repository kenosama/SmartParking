# 📄 API Documentation - Parking Spots

<details open>
<summary>🇬🇧 English version</summary>

## Base URL

```
/api/parking-spots
```

## 🔍 List User's Parking Spots

**GET** `/api/parking-spots`

### Description

Returns all parking spots created by the authenticated user, including their associated parking details.

### Response

```json
[
  {
    "id": 1,
    "identifier": "A1",
    "parking": {
      "id": 3,
      "name": "Parking Central"
    }
  }
]
```

---

## ➕ Create New Parking Spots

**POST** `/api/parking-spots`

### Parameters

| Field                  | Type    | Required | Description                                                         |
|------------------------|---------|----------|---------------------------------------------------------------------|
| identifiers            | string  | ✅        | List of spot names or ranges (e.g. "A1-A5,B1,B2-B3")                |
| parking_id             | integer | ✅        | ID of the existing parking                                          |
| allow_electric_charge  | boolean | ❌        | Is electric charging available?                                     |
| is_available           | boolean | ❌        | Is the spot available? (default: true)                              |
| per_day_only           | boolean | ❌        | Reservation allowed only by day?                                    |
| price_per_day          | number  | ❌        | Price per day                                                       |
| price_per_hour         | number  | ❌        | Price per hour                                                      |
| note                   | string  | ❌        | Informative note (max 255 characters)                               |

**Note**: If the selected parking is inactive (`is_active = false`), the newly created spots will automatically be set as unavailable (`is_available = false`). This behavior is enforced regardless of provided `is_available` value. Spots can still be created in inactive parkings by admins or owners.

### Response (201)

```json
{
  "parking": { ... },
  "user": { ... },
  "spots": [
    {
      "id": 12,
      "identifier": "A1",
      "is_available": true
    }
  ],
  "count": 3
}
```

---

## 📄 Parking Spot Details

**GET** `/api/parking-spots/{id}`

### Response

```json
{
  "spot": {
    "id": 12,
    "identifier": "A1",
    "parking": { ... },
    "user": { ... }
  },
  "proprietaire": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

## ✏️ Update a Parking Spot (admin or owner only)

**PUT** `/api/parking-spots/{id}`

### Editable Fields

| Field                  | Type    |
|------------------------|---------|
| allow_electric_charge  | boolean |
| is_available           | boolean |
| per_day_only           | boolean |
| price_per_day          | number  |
| price_per_hour         | number  |
| note                   | string  |

### Response

```json
{
  "message": "Spot updated.",
  "spot": { ... }
}
```

---

## 🗑️ Deactivate a Spot (soft delete)

**DELETE** `/api/parking-spots/{id}`

### Description

Sets the `is_available` field to `false` instead of deleting the record.

### Response

```json
{
  "message": "Spot deactivated."
}
```

---

## 🔍 Dynamic Search

Only spots belonging to active parkings and marked as available (`is_available = true`) will be returned. If the parking itself is inactive, its spots are never returned, even if marked as available.

**GET** `/api/parking-spots/search`

### Possible Parameters:

* `country=France` → returns cities available in that country.
* `zip_code=75001` → returns parkings and spots available in that zone.
* `parking_id=1` → returns available spots in that parking.
* Optionally: `start_datetime` and `end_datetime` to filter by time availability.

### Example Responses

```json
{
  "cities": ["Paris", "Lyon"]
}
```

Or

```json
{
  "parkings": [ ... ]
}
```

Or

```json
{
  "spots": [ ... ]
}
```

---

## Security

✅ Authentication is required for all routes.

</details>

<details>
<summary>🇫🇷 Version française</summary>

## URL de base

```
/api/parking-spots
```

## 🔍 Liste des spots de l'utilisateur

**GET** `/api/parking-spots`

### Description

Retourne tous les emplacements créés par l'utilisateur connecté, avec les détails du parking associé.

### Réponse

```json
[
  {
    "id": 1,
    "identifier": "A1",
    "parking": {
      "id": 3,
      "name": "Parking Central"
    }
  }
]
```

---

## ➕ Créer de nouveaux emplacements

**POST** `/api/parking-spots`

### Paramètres

| Champ                   | Type    | Obligatoire | Description                                           |
| ----------------------- | ------- | ----------- | ----------------------------------------------------- |
| identifiers             | string  | ✅           | Liste d'identifiants ou plages (ex: "A1-A5,B1,B2-B3") |
| parking_id              | integer | ✅           | ID du parking existant                                |
| allow_electric_charge   | boolean | ❌           | Prise électrique disponible ?                         |
| is_available            | boolean | ❌           | Emplacement activé ? (par défaut: true)               |
| per_day_only            | boolean | ❌           | Réservation à la journée uniquement ?                 |
| price_per_day           | number  | ❌           | Prix par jour                                         |
| price_per_hour          | number  | ❌           | Prix par heure                                        |
| note                    | string  | ❌           | Note informative max 255 caractères                   |

**Note** : Si le parking sélectionné est inactif (`is_active = false`), les nouveaux emplacements créés seront automatiquement marqués comme non disponibles (`is_available = false`). Ce comportement est appliqué systématiquement, quelle que soit la valeur fournie pour `is_available`. Les emplacements peuvent néanmoins être créés par un administrateur ou le propriétaire.

### Réponse (201)

```json
{
  "parking": { ... },
  "user": { ... },
  "spots": [
    {
      "id": 12,
      "identifier": "A1",
      "is_available": true
    }
  ],
  "count": 3
}
```

---

## 📄 Détails d’un emplacement

**GET** `/api/parking-spots/{id}`

### Réponse

```json
{
  "spot": {
    "id": 12,
    "identifier": "A1",
    "parking": { ... },
    "user": { ... }
  },
  "proprietaire": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

## ✏️ Mettre à jour un spot (admin ou owner)

**PUT** `/api/parking-spots/{id}`

### Paramètres modifiables

| Champ                   | Type    |
| ----------------------- | ------- |
| allow_electric_charge   | boolean |
| is_available            | boolean |
| per_day_only            | boolean |
| price_per_day           | number  |
| price_per_hour          | number  |
| note                    | string  |

### Réponse

```json
{
  "message": "Place mise à jour.",
  "spot": { ... }
}
```

---

## 🗑️ Désactiver (soft delete) un spot

**DELETE** `/api/parking-spots/{id}`

### Description

Change le champ `is_available` à `false` (soft delete).

### Réponse

```json
{
  "message": "Place désactivée."
}
```

---

## 🔍 Recherche dynamique

Seuls les spots appartenant à des parkings actifs et marqués comme disponibles (`is_available = true`) seront retournés. Si le parking est inactif, ses spots ne seront jamais retournés, même s'ils sont marqués disponibles.

**GET** `/api/parking-spots/search`

### Paramètres possibles :

* `country=France` → retourne les villes disponibles dans ce pays.
* `zip_code=75001` → retourne parkings et spots disponibles dans cette zone.
* `parking_id=1` → retourne les spots disponibles dans ce parking.
* Optionnel : `start_datetime` et `end_datetime` pour filtrer selon les disponibilités.

### Réponse type

```json
{
  "cities": ["Paris", "Lyon"]
}
```

Ou

```json
{
  "parkings": [ ... ]
}
```

Ou

```json
{
  "spots": [ ... ]
}
```

---

## Sécurité

✅ Authentification requise pour accéder à toutes les routes.

</details>
