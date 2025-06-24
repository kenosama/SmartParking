# 📘 Documentation API - Parkings

Cette documentation couvre les routes disponibles pour la gestion des **parkings** dans l'API Laravel.

---

## 🔐 Toutes les routes nécessitent une authentification via token Bearer.

---

## 🔄 Liste des parkings

- **Méthode** : `GET`
- **URL** : `/api/parkings`
- **Description** : Récupère tous les parkings créés par l'utilisateur connecté.
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
- **Réponse attendue** :
```json
{
  "message": "Parking créé avec succès",
  "parking": {
    "id": 1,
    "name": "Parking Nord",
    ...
  }
}
```

---

## 📝 Mettre à jour un parking

- **Méthode** : `PUT`
- **URL** : `/api/parkings/{id}`
- **Champs modifiables** :
  - `name`, `street`, `location_number`, `zip_code`, `city`, `country`, `total_capacity`, `is_open_24h`, `opening_hours`, `opening_days`
- **Exemple** :
```json
{
  "name": "Parking Sud",
  "total_capacity": 25
}
```

---

## ❌ Supprimer un parking

- **Méthode** : `DELETE`
- **URL** : `/api/parkings/{id}`
- **Description** : Supprime un parking appartenant à l'utilisateur connecté.

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