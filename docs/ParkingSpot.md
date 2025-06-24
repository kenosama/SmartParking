# Documentation de l'API - Parking Spots

## Base URL

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
| parking\_id             | integer | ✅           | ID du parking existant                                |
| allow\_electric\_charge | boolean | ❌           | Prise électrique disponible ?                         |
| is\_available           | boolean | ❌           | Emplacement activé ? (par défaut: true)               |
| per\_day\_only          | boolean | ❌           | Réservation à la journée uniquement ?                 |
| price\_per\_day         | number  | ❌           | Prix par jour                                         |
| price\_per\_hour        | number  | ❌           | Prix par heure                                        |
| note                    | string  | ❌           | Note informative max 255 caractères                   |

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
| allow\_electric\_charge | boolean |
| is\_available           | boolean |
| per\_day\_only          | boolean |
| price\_per\_day         | number  |
| price\_per\_hour        | number  |
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
