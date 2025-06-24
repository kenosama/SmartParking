# 🔐 Authentication API Documentation

Ce fichier détaille les différentes requêtes liées à l’authentification des utilisateurs de l’API SmartParking.

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
