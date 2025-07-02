


# 👤 UserController — API Reference

<details open>
<summary>🇬🇧 English Version</summary>

## Overview

This document details the public API endpoints exposed by the `UserController`.

---

### 📘 `GET /api/users`

Returns the list of all users.  
**Access:** Admin only.

#### ✅ Example request

```http
GET /api/users HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Example response

```json
[
  {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com",
    "is_active": true
  },
  ...
]
```

---

### 📘 `GET /api/users/{id}`

Returns the user data for a given ID or email.  
**Access:** Admin or the user himself.

#### ✅ Example request

```http
GET /api/users/2 HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Example response

```json
{
  "id": 2,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com"
}
```

---

### 📘 `PATCH /api/users/{id}`

Updates the user information.  
**Access:** Admin or the user himself.

#### ✅ Request body

```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "jane@example.com",
  "password": "newpassword123"
}
```

#### ✅ Example response

```json
{
  "message": "User updated successfully.",
  "user": {
    "id": 2,
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com"
  }
}
```

---

### 📘 `DELETE /api/users/{id}`

Soft deletes a user (sets `is_active = false`).  
**Access:** Admin only.

#### ✅ Example request

```http
DELETE /api/users/3 HTTP/1.1
Authorization: Bearer <token>
```

#### ✅ Example response

```http
HTTP/1.1 204 No Content
```

---

### 📘 `POST /register`

Registers a new user.  
**Access:** Public

#### ✅ Request body

```json
{
  "first_name": "Alice",
  "last_name": "Smith",
  "email": "alice@example.com",
  "password": "securePassword123",
  "password_confirmation": "securePassword123",
  "is_owner": true,
  "is_tenant": true
}
```

#### ✅ Example response

```json
{
  "user": {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com",
    "is_owner": true,
    "is_tenant": true,
    "is_admin": false,
    "is_active": true,
    ...
  },
  "token": "2|vLh1eH..."
}
```

---

### 📘 `POST /login`

Authenticates the user and returns an API token.  
**Access:** Public

#### ✅ Request body

```json
{
  "email": "alice@example.com",
  "password": "securePassword123"
}
```

#### ✅ Example response

```json
{
  "user": {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com"
  },
  "token": "2|aDqEK..."
}
```

---

### 📘 `POST /logout`

Revokes the current access token (logs out the user).  
**Access:** Authenticated users only.

#### ✅ Example request

```http
POST /logout HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Example response

```json
{
  "message": "Logged out successfully"
}
```

</details>

---

<details>
<summary>🇫🇷 Version française</summary>

## Vue d’ensemble

Ce document décrit les endpoints publics exposés par le `UserController`.

---

### 📘 `GET /api/users`

Retourne la liste de tous les utilisateurs.  
**Accès :** uniquement administrateur.

#### ✅ Exemple de requête

```http
GET /api/users HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Exemple de réponse

```json
[
  {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com",
    "is_active": true
  },
  ...
]
```

---

### 📘 `GET /api/users/{id}`

Retourne les informations d’un utilisateur (par ID ou email).  
**Accès :** admin ou utilisateur concerné.

#### ✅ Exemple de requête

```http
GET /api/users/2 HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Exemple de réponse

```json
{
  "id": 2,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com"
}
```

---

### 📘 `PATCH /api/users/{id}`

Met à jour les données d’un utilisateur.  
**Accès :** admin ou utilisateur concerné.

#### ✅ Corps de la requête

```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "jane@example.com",
  "password": "newpassword123"
}
```

#### ✅ Exemple de réponse

```json
{
  "message": "User updated successfully.",
  "user": {
    "id": 2,
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com"
  }
}
```

---

### 📘 `DELETE /api/users/{id}`

Désactive un utilisateur (`is_active = false`).  
**Accès :** uniquement administrateur.

#### ✅ Exemple de requête

```http
DELETE /api/users/3 HTTP/1.1
Authorization: Bearer <token>
```

#### ✅ Exemple de réponse

```http
HTTP/1.1 204 No Content
```

---

### 📘 `POST /register`

Inscrit un nouvel utilisateur.  
**Accès :** Public

#### ✅ Corps de la requête

```json
{
  "first_name": "Alice",
  "last_name": "Smith",
  "email": "alice@example.com",
  "password": "securePassword123",
  "password_confirmation": "securePassword123",
  "is_owner": true,
  "is_tenant": true
}
```

#### ✅ Exemple de réponse

```json
{
  "user": {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com",
    "is_owner": true,
    "is_tenant": true,
    "is_admin": false,
    "is_active": true,
    ...
  },
  "token": "2|vLh1eH..."
}
```

---

### 📘 `POST /login`

Authentifie un utilisateur et retourne un token d’API.  
**Accès :** Public

#### ✅ Corps de la requête

```json
{
  "email": "alice@example.com",
  "password": "securePassword123"
}
```

#### ✅ Exemple de réponse

```json
{
  "user": {
    "id": 1,
    "first_name": "Alice",
    "last_name": "Smith",
    "email": "alice@example.com"
  },
  "token": "2|aDqEK..."
}
```

---

### 📘 `POST /logout`

Révoque le token d’accès actuel (déconnexion de l’utilisateur).  
**Accès :** Utilisateur authentifié uniquement.

#### ✅ Exemple de requête

```http
POST /logout HTTP/1.1
Authorization: Bearer <token>
Accept: application/json
```

#### ✅ Exemple de réponse

```json
{
  "message": "Déconnexion réussie"
}
```

</details>