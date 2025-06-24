# 📘 users.md — API Users

<details open>
<summary>🇬🇧 English version (click to collapse)</summary>

---

## 📄 Get user details

- **Method**: `GET`
- **URL**: `/api/user/{id|email}`
- **Description**: Returns the details of a specific user. Accessible by the user themselves or by an admin.
- **Response**:
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "is_active": true
  }
}
```

## ✏️ Update a user
- **Method**: `PUT`
- **URL**: `/api/user/{id|email}`
- **Fields accepted**:
```json
{
  "first_name": "New first name",
  "last_name": "New last name",
  "email": "new@mail.com",
  "password": "newpassword"
}
```
- **Note**: A user can update their own data; an admin can update any user.

## 🗑️ Deactivate a user (Soft delete)
- **Method**: `DELETE`
- **URL**: `/api/user/{id|email}`
- **Description**: Deactivates a user (soft delete by setting `is_active = false`).
- **Rules**:
  - A user can deactivate themselves.
  - An admin can deactivate any user except another admin.

## ✅ Reactivate a user
- **Method**: `PATCH`
- **URL**: `/api/user/{id|email}/reactivate`
- **Description**: Reactivates a previously deactivated user.
- **Access**: Admin only.

</details>

<details>
<summary>🇫🇷 Version française (cliquez pour déplier)</summary>

---

## 📄 Lire les détails d’un utilisateur

- **Méthode** : `GET`
- **URL** : `/api/user/{id|email}`
- **Description** : Retourne les informations d’un utilisateur spécifique. Accessible à l’utilisateur lui-même ou à un administrateur.
- **Réponse** :
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "is_active": true
  }
}
```

## ✏️ Mettre à jour un utilisateur
- **Méthode** : `PUT`
- **URL** : `/api/user/{id|email}`
- **Champs acceptés** :
```json
{
  "first_name": "Nouveau prénom",
  "last_name": "Nouveau nom",
  "email": "nouveau@mail.com",
  "password": "nouveaumotdepasse"
}
```
- **Note** : Un utilisateur peut modifier ses propres données, un admin peut modifier n’importe qui.

## 🗑️ Désactiver un utilisateur (Soft delete)
- **Méthode** : `DELETE`
- **URL** : `/api/user/{id|email}`
- **Description** : Désactive un utilisateur (soft delete via champ `is_active = false`).
- **Règles** :
  - Un utilisateur peut se désactiver lui-même.
  - Un admin peut désactiver n’importe qui sauf un autre admin.

## ✅ Réactiver un utilisateur
- **Méthode** : `PATCH`
- **URL** : `/api/user/{id|email}/reactivate`
- **Description** : Réactive un utilisateur précédemment désactivé.
- **Accès** : Réservé aux administrateurs.

</details>
