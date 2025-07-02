# 🚗 ParkingController – API Reference
<details open>
<summary>🇬🇧 English Version</summary>

### 📘 `GET /api/parkings`

Returns a list of parkings available to the authenticated user.

- **Admins**: receive all parkings grouped by creator.
- **Owners/Co-owners**: receive parkings they created or co-own.
- **Response**: includes co-owner info.

#### 🔐 Authentication required

#### ✅ Example response

```json
[
  {
    "user": {
      "full_name": "John Doe",
      "email": "john@example.com"
    },
    "parkings": [
      {
        "id": 1,
        "name": "Main Lot",
        "city": "Paris",
        "co_owners": [...]
      }
    ]
  }
]
```

---

### 📘 `POST /api/parkings`

Creates a new parking.  
Only **admins** and **active owners** (`is_owner = true` and `is_active = true`) can create a parking.

#### 📝 Required body

```json
{
  "name": "Downtown Parking",
  "street": "Rue de Rivoli",
  "location_number": "42",
  "zip_code": "75001",
  "city": "Paris",
  "country": "France",
  "total_capacity": 20,
  "is_open_24h": false,
  "opening_hours": "08:00-20:00",
  "opening_days": "1-5"
}
```

#### ✅ Example response

Returns the created parking with `201 Created`.

---

### 📘 `GET /api/parkings/{id}`

Shows detailed info about a specific parking.  
Access is allowed for **admins**, the **creator**, or a **co-owner**.

#### ✅ Example response

```json
{
  "id": 1,
  "name": "Main Lot",
  "city": "Paris",
  "co_owners": [...]
}
```

---

### 📘 `PUT /api/parkings/{id}`

Updates a parking.  
Only **admins** or the **creator** can update it.

- Can transfer ownership by `user_email`.
- Can deactivate the parking and disable all spots.

#### 📝 Optional body

```json
{
  "city": "Lyon",
  "is_active": false,
  "user_email": "newowner@example.com"
}
```

---

### 📘 `DELETE /api/parkings/{id}`

Soft-deletes a parking (`is_active = false`) and disables all related spots.  
Access allowed for **admins** and **creator**.

#### ✅ Example response

```json
{
  "message": "Parking soft-deleted (is_active = false)"
}
```

</details>

<details>

# 🚗 ParkingController – Référence API

<summary>🇫🇷 Version française</summary>
---

### 📘 `GET /api/parkings`

Retourne la liste des parkings accessibles à l’utilisateur authentifié.

- **Admins** : tous les parkings groupés par créateur.
- **Propriétaires / Co-propriétaires** : parkings créés ou co-gérés.
- **Réponse** : inclut les co-propriétaires.

#### 🔐 Authentification requise

#### ✅ Exemple de réponse

```json
[
  {
    "user": {
      "full_name": "John Doe",
      "email": "john@example.com"
    },
    "parkings": [
      {
        "id": 1,
        "name": "Main Lot",
        "city": "Paris",
        "co_owners": [...]
      }
    ]
  }
]
```

---

### 📘 `POST /api/parkings`

Crée un nouveau parking.  
Seuls les **admins** et **propriétaires actifs** (`is_owner = true` et `is_active = true`) peuvent créer un parking.

#### 📝 Corps requis

```json
{
  "name": "Downtown Parking",
  "street": "Rue de Rivoli",
  "location_number": "42",
  "zip_code": "75001",
  "city": "Paris",
  "country": "France",
  "total_capacity": 20,
  "is_open_24h": false,
  "opening_hours": "08:00-20:00",
  "opening_days": "1-5"
}
```

#### ✅ Exemple de réponse

Retourne le parking créé avec `201 Created`.

---

### 📘 `GET /api/parkings/{id}`

Affiche les détails d’un parking spécifique.  
Accès autorisé pour **admin**, **créateur** ou **co-propriétaire**.

#### ✅ Exemple de réponse

```json
{
  "id": 1,
  "name": "Main Lot",
  "city": "Paris",
  "co_owners": [...]
}
```

---

### 📘 `PUT /api/parkings/{id}`

Met à jour un parking.  
Seuls les **admins** ou le **créateur** peuvent le modifier.

- Peut changer le propriétaire via `user_email`.
- Peut désactiver le parking et tous ses spots.

#### 📝 Corps possible

```json
{
  "city": "Lyon",
  "is_active": false,
  "user_email": "newowner@example.com"
}
```

---

### 📘 `DELETE /api/parkings/{id}`

Supprime logiquement un parking (`is_active = false`) et désactive tous les spots liés.  
Accès autorisé pour **admins** et **créateur**.

#### ✅ Exemple de réponse

```json
{
  "message": "Parking soft-deleted (is_active = false)"
}
```

</details>