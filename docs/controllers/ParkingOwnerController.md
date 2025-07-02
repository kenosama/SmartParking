

<details open>
<summary>🇬🇧 English Version</summary>

# 🧠 ParkingOwnerController – Internal Logic

This controller manages the addition, removal, and listing of co-owners for a given parking.

All methods require authentication via `auth:sanctum`.

---

## 📘 `store(Request $request, Parking $parking)`

### Role
Adds one or more co-owners to a parking.

### Access Control
- Only the parking's creator or an admin can add co-owners.

### Logic
- Validates that `emails` is an array of existing emails.
- Fetches all users corresponding to the provided emails.
- Associates each user as a `co_owner` without removing existing ones.
- Returns a success message.

---

## 📘 `destroy(Request $request, Parking $parking)`

### Role
Removes a co-owner from a parking.

### Access Control
- Only the parking's creator or an admin can remove co-owners.

### Logic
- Validates that the provided email exists.
- Fetches the user with the given email.
- Detaches the user from the `coOwners` pivot table.
- Returns a success message.

---

## 📘 `index(Parking $parking)`

### Role
Lists all co-owners of a parking.

### Access Control
- Only the parking's creator or an admin can list co-owners.

### Logic
- Fetches co-owners' basic info (`id`, `first_name`, `last_name`, `email`).
- Returns them as a JSON response.

</details>

<details>
<summary>🇫🇷 Version française</summary>

# 🧠 ParkingOwnerController – Logique interne

Ce contrôleur gère l’ajout, la suppression et la liste des co-propriétaires pour un parking donné.

Toutes les méthodes nécessitent une authentification via `auth:sanctum`.

---

## 📘 `store(Request $request, Parking $parking)`

### Rôle
Ajoute un ou plusieurs co-propriétaires à un parking.

### Contrôle d’accès
- Seul le créateur du parking ou un admin peut ajouter des co-propriétaires.

### Logique
- Valide que `emails` est un tableau d’adresses valides et existantes.
- Récupère les utilisateurs correspondants.
- Les associe en tant que `co_owner` sans détacher les existants.
- Retourne un message de succès.

---

## 📘 `destroy(Request $request, Parking $parking)`

### Rôle
Supprime un co-propriétaire d’un parking.

### Contrôle d’accès
- Seul le créateur du parking ou un admin peut supprimer un co-propriétaire.

### Logique
- Valide que l’email fourni existe.
- Récupère l’utilisateur.
- Le détache du pivot `coOwners`.
- Retourne un message de succès.

---

## 📘 `index(Parking $parking)`

### Rôle
Liste tous les co-propriétaires d’un parking.

### Contrôle d’accès
- Seul le créteur du parking ou un admin peut afficher les co-propriétaires.

### Logique
- Récupère les infos de base des co-propriétaires (`id`, `first_name`, `last_name`, `email`).
- Retourne ces infos dans une réponse JSON.

</details>