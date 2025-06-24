# 📘 API Documentation - Parkings

<details open>
<summary>🇬🇧 English Version</summary>

This documentation covers the available routes for managing **parkings** in the Laravel API.

---

## 🔐 All routes require authentication via Bearer token.

---

## 🔄 List Parkings

- **Method**: `GET`
- **URL**: `/api/parkings`
- **Description**: Retrieves all active parkings created by the authenticated user.
- **Response example**:
```json
[
  {
    "id": 1,
    "name": "Parking Nord",
    "street": "Rue des Lilas",
    "location_number": "123",
    "zip_code": "1000",
    "city": "Brussels",
    "country": "Belgium",
    "total_capacity": 20,
    "is_open_24h": true,
    "opening_hours": null,
    "opening_days": null
  },
  ...
]
```

---

## ➕ Create a new Parking

- **Method**: `POST`
- **URL**: `/api/parkings`
- **Required fields**:
```json
{
  "name": "Parking Nord",
  "street": "Rue des Lilas",
  "location_number": "123",
  "zip_code": "1000",
  "city": "Brussels",
  "country": "Belgium",
  "total_capacity": 20,
  "is_open_24h": true,
  "opening_hours": null,
  "opening_days": null
}
```
- **Note**: `is_active` is set to `true` by default.
- **Response example**:
```json
{
  "message": "Parking successfully created",
  "parking": {
    "id": 1,
    "name": "Parking Nord",
    "is_active": true,
    ...
  }
}
```

---

## 📝 Update a Parking

- **Method**: `PUT`
- **URL**: `/api/parkings/{id}`
- **Updatable fields**:
  - `name`, `street`, `location_number`, `zip_code`, `city`, `country`, `total_capacity`, `is_open_24h`, `opening_hours`, `opening_days`, `is_active`
- **Example**:
```json
{
  "name": "Parking Sud",
  "total_capacity": 25,
  "is_active": true
}
```

---

## ❌ Delete a Parking

- **Method**: `DELETE`
- **URL**: `/api/parkings/{id}`
- **Description**: Performs a **soft delete** by setting `is_active` to false instead of deleting the resource.

---

## 📄 Parking Details

- **Method**: `GET`
- **URL**: `/api/parkings/{id}`
- **Response**:
```json
{
  "id": 1,
  "name": "Parking Nord",
  "user_id": 2,
  ...
}
```

</details>

<details>
<summary>🇫🇷 Version Française</summary>

Cette documentation couvre les routes disponibles pour la gestion des **parkings** dans l'API Laravel.

---

## 🔐 Toutes les routes nécessitent une authentification via token Bearer.

---

## 🔄 Liste des parkings

- **Méthode** : `GET`
- **URL** : `/api/parkings`
- **Description** : Récupère tous les parkings actifs créés par l'utilisateur connecté.
- **Réponse attendue** :
```json
[
  {
    "id": 1,
    "name": "Parking Nord",
    "street": "Rue des Lilas",
    "location_number": "123",
    "zip_code": "1000",
    "city": "Bruxelles",
    "country": "Belgique",
    "total_capacity": 20,
    "is_open_24h": true,
    "opening_hours": null,
    "opening_days": null
  },
  ...
]
```

---

## ➕ Créer un nouveau parking

- **Méthode** : `POST`
- **URL** : `/api/parkings`
- **Champs requis** :
```json
{
  "name": "Parking Nord",
  "street": "Rue des Lilas",
  "location_number": "123",
  "zip_code": "1000",
  "city": "Bruxelles",
  "country": "Belgique",
  "total_capacity": 20,
  "is_open_24h": true,
  "opening_hours": null,
  "opening_days": null
}
```
- **Note** : `is_active` est défini à `true` par défaut.
- **Réponse attendue** :
```json
{
  "message": "Parking créé avec succès",
  "parking": {
    "id": 1,
    "name": "Parking Nord",
    "is_active": true,
    ...
  }
}
```

---

## 📝 Mettre à jour un parking

- **Méthode** : `PUT`
- **URL** : `/api/parkings/{id}`
- **Champs modifiables** :
  - `name`, `street`, `location_number`, `zip_code`, `city`, `country`, `total_capacity`, `is_open_24h`, `opening_hours`, `opening_days`, `is_active`
- **Exemple** :
```json
{
  "name": "Parking Sud",
  "total_capacity": 25,
  "is_active": true
}
```

---

## ❌ Supprimer un parking

- **Méthode** : `DELETE`
- **URL** : `/api/parkings/{id}`
- **Description** : Effectue une suppression douce en mettant `is_active` à false au lieu de supprimer la ressource.

---

## 📄 Détail d’un parking

- **Méthode** : `GET`
- **URL** : `/api/parkings/{id}`
- **Réponse** :
```json
{
  "id": 1,
  "name": "Parking Nord",
  "user_id": 2,
  ...
}
```

</details>