


# 🧠 UserController — Internal Logic

<details open>
<summary>🇬🇧 English Version</summary>

## Overview

This document explains the internal flow and behavior of each method inside the `UserController`, including both public endpoints and private helpers.

---

### 🔹 `index()`

Returns a list of all users. Only accessible to admins.

- Authorization: `Gate::authorize('admin-only')`
- Returns: JSON collection of users.

---

### 🔹 `show($id)`

Displays a specific user's details.

- Authorization: User must be the owner of the data or an admin.
- Uses helper: `getUserModel($id)`
- Returns: JSON user data or 403/404 errors.

---

### 🔹 `update(Request $request, $id)`

Updates user info.

- Validation: First name, last name, email format.
- Password is re-hashed if provided.
- Authorization: Self or admin.
- Uses helper: `getUserModel($id)`
- Returns: JSON of updated user.

---

### 🔹 `destroy($id)`

Soft-deletes a user (sets `is_active` to false).

- Authorization: Admin only.
- Returns: 204 no content.

---

### 🔹 `getUserModel($id)`

Private method to fetch user by ID or email.

- If user not found → throws 404.
- Used by `show()` and `update()`.

---

### 🔹 `store(Request $request)`

Registers a new user account.

- Validates: `first_name`, `last_name`, `email`, `password` (confirmed), `is_owner`, `is_tenant`.
- Forces:
  - `is_active = true`
  - `is_admin = false`
- Hashes the password before saving.
- Automatically logs in the user.
- Returns a JSON response with user data and an API token (201 Created).

</details>

---

<details>
<summary>🇫🇷 Version française</summary>

## Vue d’ensemble

Ce document détaille le fonctionnement interne des méthodes du `UserController`, qu’elles soient publiques (API) ou privées (helpers internes).

---

### 🔹 `index()`

Retourne la liste de tous les utilisateurs. Accessible uniquement aux administrateurs.

- Autorisation : `Gate::authorize('admin-only')`
- Retour : collection JSON des utilisateurs.

---

### 🔹 `show($id)`

Affiche les détails d’un utilisateur.

- Autorisation : doit être admin ou propriétaire de la ressource.
- Utilise la méthode : `getUserModel($id)`
- Retourne : données JSON de l’utilisateur ou erreur 403/404.

---

### 🔹 `update(Request $request, $id)`

Met à jour les informations d’un utilisateur.

- Validation : prénom, nom, email.
- Re-hash du mot de passe si fourni.
- Autorisation : soi-même ou admin.
- Utilise : `getUserModel($id)`
- Retourne : l’utilisateur mis à jour en JSON.

---

### 🔹 `destroy($id)`

Désactive un utilisateur (`is_active = false`).

- Autorisation : uniquement admin.
- Retour : 204 no content.

---

### 🔹 `getUserModel($id)`

Méthode privée pour récupérer un utilisateur via ID ou email.

- Lève une 404 si non trouvé.
- Utilisée dans `show()` et `update()`.

---

### 🔹 `store(Request $request)`

Inscrit un nouvel utilisateur.

- Valide : `first_name`, `last_name`, `email`, `password` (confirmé), `is_owner`, `is_tenant`.
- Force les champs :
  - `is_active = true`
  - `is_admin = false`
- Hash le mot de passe avant enregistrement.
- Connecte automatiquement l’utilisateur après création.
- Retourne une réponse JSON avec les données de l’utilisateur et un token d’API (201 Created).

</details>