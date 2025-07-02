


<details open>
<summary>🇬🇧 English Version</summary>

# 👥 ParkingOwnerController – API Reference

This controller manages co-owners of a parking lot.

---

### 📘 `POST /api/parkings/{parking}/coowners`

Adds one or more co-owners to a parking.

#### 📝 Required body

```json
{
  "emails": ["user1@example.com", "user2@example.com"]
}
```

#### 🔐 Permissions

- Only the **creator** of the parking or an **admin** can use this route.

#### ✅ Response

```json
{
  "message": "Co-owners added successfully."
}
```

---

### 📘 `DELETE /api/parkings/{parking}/coowners`

Removes a co-owner from a parking.

#### 📝 Required body

```json
{
  "email": "user1@example.com"
}
```

#### 🔐 Permissions

- Only the **creator** or an **admin** can remove co-owners.

#### ✅ Response

```json
{
  "message": "Co-owner removed successfully."
}
```

---

### 📘 `GET /api/parkings/{parking}/coowners`

Returns a list of co-owners for the given parking.

#### 🔐 Permissions

- Only the **creator** or an **admin** can view the list.

#### ✅ Response

```json
{
  "co_owners": [
    {
      "id": 3,
      "first_name": "Jane",
      "last_name": "Doe",
      "email": "jane@example.com"
    }
  ]
}
```

</details>

<details>
<summary>🇫🇷 Version française</summary>

# 👥 ParkingOwnerController – Référence API

Ce contrôleur gère les co-propriétaires d’un parking.

---

### 📘 `POST /api/parkings/{parking}/coowners`

Ajoute un ou plusieurs co-propriétaires à un parking.

#### 📝 Corps requis

```json
{
  "emails": ["user1@example.com", "user2@example.com"]
}
```

#### 🔐 Permissions

- Seul le **créateur** du parking ou un **admin** peut utiliser cette route.

#### ✅ Réponse

```json
{
  "message": "Co-owners added successfully."
}
```

---

### 📘 `DELETE /api/parkings/{parking}/coowners`

Supprime un co-propriétaire d’un parking.

#### 📝 Corps requis

```json
{
  "email": "user1@example.com"
}
```

#### 🔐 Permissions

- Seul le **créateur** ou un **admin** peut retirer un co-propriétaire.

#### ✅ Réponse

```json
{
  "message": "Co-owner removed successfully."
}
```

---

### 📘 `GET /api/parkings/{parking}/coowners`

Retourne la liste des co-propriétaires pour un parking donné.

#### 🔐 Permissions

- Seul le **créateur** ou un **admin** peut voir la liste.

#### ✅ Réponse

```json
{
  "co_owners": [
    {
      "id": 3,
      "first_name": "Jane",
      "last_name": "Doe",
      "email": "jane@example.com"
    }
  ]
}
```

</details>