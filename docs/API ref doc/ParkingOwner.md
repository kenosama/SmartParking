

# 👥 API Documentation – Parking Co-Owners

<details open>
<summary>🇬🇧 English Version</summary>

This section documents the API for managing co-owners of a parking lot.

---

## 🔐 All routes require authentication via Bearer token.

---

## 👥 List Co-Owners

- **Method**: `GET`
- **URL**: `/api/parkings/{parking}/co-owners`
- **Access**: Admins or the parking's creator only
- **Response example**:
```json
{
  "co_owners": [
    {
      "id": 3,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com"
    },
    ...
  ]
}
```

---

## ➕ Add Co-Owners (Batch)

- **Method**: `POST`
- **URL**: `/api/parkings/{parking}/co-owners`
- **Access**: Admins or the parking's creator only
- **Required payload**:
```json
{
  "emails": [
    "john@example.com",
    "jane@example.com"
  ]
}
```
- **Response example**:
```json
{
  "message": "Co-owners added successfully."
}
```

---

## ❌ Remove a Co-Owner

- **Method**: `DELETE`
- **URL**: `/api/parkings/{parking}/co-owners`
- **Access**: Admins or the parking's creator only
- **Required payload**:
```json
{
  "email": "john@example.com"
}
```
- **Response example**:
```json
{
  "message": "Co-owner removed successfully."
}
```

</details>

<details>
<summary>🇫🇷 Version Française</summary>

Cette section documente l’API de gestion des co-propriétaires d’un parking.

---

## 🔐 Toutes les routes nécessitent une authentification via token Bearer.

---

## 👥 Lister les co-propriétaires

- **Méthode** : `GET`
- **URL** : `/api/parkings/{parking}/co-owners`
- **Accès** : Admins ou créateur du parking uniquement
- **Réponse attendue** :
```json
{
  "co_owners": [
    {
      "id": 3,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com"
    },
    ...
  ]
}
```

---

## ➕ Ajouter des co-propriétaires (par lot)

- **Méthode** : `POST`
- **URL** : `/api/parkings/{parking}/co-owners`
- **Accès** : Admins ou créateur du parking uniquement
- **Corps de requête requis** :
```json
{
  "emails": [
    "john@example.com",
    "jane@example.com"
  ]
}
```
- **Réponse attendue** :
```json
{
  "message": "Co-owners added successfully."
}
```

---

## ❌ Supprimer un co-propriétaire

- **Méthode** : `DELETE`
- **URL** : `/api/parkings/{parking}/co-owners`
- **Accès** : Admins ou créateur du parking uniquement
- **Corps de requête requis** :
```json
{
  "email": "john@example.com"
}
```
- **Réponse attendue** :
```json
{
  "message": "Co-owner removed successfully."
}
```

</details>