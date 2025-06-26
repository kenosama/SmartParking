# 🔐 Authentication API Documentation

<details open>
<summary>🇬🇧 English Version</summary>

---

## 📝 Register

**POST** `/api/register`

**Required fields:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Success Response:**

```json
{
  "message": "User successfully registered",
  "token": "<TOKEN>"
}
```

---

## 🔐 Login

**POST** `/api/login`

**Required fields:**

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Success Response:**

```json
{
  "message": "Login successful",
  "token": "<TOKEN>",
  "user": {
    "id": 1,
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe"
  }
}
```

**Error Response:**

```json
{
  "error": "Invalid credentials"
}
```

---

## 🔒 Logout

**POST** `/api/logout`

**Required header:**

```
Authorization: Bearer <TOKEN>
```

**Response:**

```json
{
  "message": "User logged out"
}
```

---

## 🔐 Notes

* All protected routes require a valid token in the header: `Authorization: Bearer <TOKEN>`
* If the token is expired or revoked, the user will need to login again.

---

📁 Back to summary: [../README.md](../README.md)

</details>

---

<details>
<summary>🇫🇷 Version Française</summary>

---

## 📝 Register (Inscription)

**POST** `/api/register`

**Champs requis :**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Réponse (succès) :**

```json
{
  "message": "User successfully registered",
  "token": "<TOKEN>"
}
```

---

## 🔐 Login (Connexion)

**POST** `/api/login`

**Champs requis :**

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Réponse (succès) :**

```json
{
  "message": "Login successful",
  "token": "<TOKEN>",
  "user": {
    "id": 1,
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe"
  }
}
```

**Réponse (erreur) :**

```json
{
  "error": "Invalid credentials"
}
```

---

## 🔒 Logout (Déconnexion)

**POST** `/api/logout`

**Header requis :**

```
Authorization: Bearer <TOKEN>
```

**Réponse :**

```json
{
  "message": "User logged out"
}
```

---

## 🔐 Notes complémentaires

* Toutes les routes protégées nécessitent un token d’accès via le header `Authorization: Bearer <TOKEN>`
* En cas d’expiration ou de révocation du token, l’utilisateur devra se reconnecter.

---

📁 Retour au sommaire : [../README.md](../README.md)

</details>
