# 📘 users.md — API Utilisateurs

## 🔐 Authentification requise
Toutes les routes ci-dessous nécessitent un token Bearer JWT valide, obtenu via l’authentification (voir `authentication.md`).

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
•	Méthode : PUT
•	URL : /api/user/{id|email}
•	Champs acceptés :

```json
{
  "first_name": "Nouveau prénom",
  "last_name": "Nouveau nom",
  "email": "nouveau@mail.com",
  "password": "nouveaumotdepasse"
}
```
•	Note : Un utilisateur peut modifier ses propres données, un admin peut modifier n’importe qui.

## 🗑️ Désactiver un utilisateur (Soft delete)
•	Méthode : DELETE
•	URL : /api/user/{id|email}
•	Description : Désactive un utilisateur (soft delete via champ is_active = false).
•	Règles :
•	Un utilisateur peut se désactiver lui-même.
•	Un admin peut désactiver n’importe qui sauf un autre admin.

## ✅ Réactiver un utilisateur
•	Méthode : PATCH
•	URL : /api/user/{id|email}/reactivate
•	Description : Réactive un utilisateur précédemment désactivé.
•	Accès : Réservé aux administrateurs.

## 🔍 Notes supplémentaires
•	Les identifiants utilisateur peuvent être l’id numérique ou l’email.
•	Les routes sont sécurisées par middleware auth:sanctum.
•	Pour tester dans Postman, ajouter le header :
```http
Authorization: Bearer {TOKEN}
```
