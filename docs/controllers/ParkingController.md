


<details open>
<summary>🇬🇧 English Version</summary>

# 🧠 ParkingController – Internal Logic

This document describes the internal behavior, conditions, and structure of the `ParkingController` class.

---

## 🔐 Middleware

- All routes are protected by `auth:sanctum`.

---

## 📘 `index()`

### Role
Returns a list of parkings based on user role:
- Admins: all parkings grouped by owner with co-owners.
- Owners/co-owners: parkings they created or co-own.

### Logic
- Loads related `user` and `coOwners`.
- Groups by creator for admins.
- Returns JSON with user info and owned/co-owned parkings.
- If no parking found: returns 403.

---

## 📘 `store(Request $request)`

### Role
Creates a new parking.

### Access Control
- Only `admin` or `is_owner && is_active`.

### Logic
- Validates input.
- Automatically sets `user_id`, `is_active = true`.
- If open 24h: overrides opening hours/days.
- Adds creator as co-owner in pivot.

---

## 📘 `show(Parking $parking)`

### Role
Displays a specific parking.

### Access Control
- Only admin, creator, or co-owners.

### Logic
- Loads co-owners.
- Returns formatted parking.

---

## 📘 `update(Request $request, Parking $parking)`

### Role
Updates a parking's information.

### Access Control
- Only admin or creator.

### Logic
- Validates inputs.
- If `user_email`: transfers ownership using DB transaction.
- Updates `is_active`:
  - `false` → disables all spots.
  - `true` → re-enables all spots if authorized.

---

## 📘 `destroy(Parking $parking)`

### Role
Soft deletes a parking.

### Access Control
- Only admin or creator.

### Logic
- Calls internal method to deactivate and disable all spots.

---

## 🛠️ Private Helpers

### `processOpeningDaysAndHours(Request $request)`
- If open 24h: sets days to 1–7 and hours to 00:00–23:59.
- Expands `"1-5"` to `"1,2,3,4,5"`.

### `validationRules(bool $isUpdate = false)`
- Dynamically defines rules for creation or update.

### `deactivateParkingAndSpots(Parking $parking)`
- Disables parking and its related spots.

### `formatUserInfo(User $user)`
- Returns full name + email.

### `formatParkingWithCoOwners(Parking $parking)`
- Returns parking info with co-owner details.

</details>

<details>
<summary>🇫🇷 Version française</summary>

# 🧠 ParkingController – Logique interne

Ce document décrit le comportement interne, les conditions et la structure du contrôleur `ParkingController`.

---

## 🔐 Middleware

- Toutes les routes sont protégées par `auth:sanctum`.

---

## 📘 `index()`

### Rôle
Retourne une liste de parkings selon le rôle :
- Admins : tous les parkings groupés par propriétaire.
- Propriétaires ou co-propriétaires : parkings créés ou co-gérés.

### Logique
- Charge les relations `user` et `coOwners`.
- Groupe par créateur pour les admins.
- Retourne un JSON structuré.
- Si aucun parking : 403.

---

## 📘 `store(Request $request)`

### Rôle
Crée un parking.

### Contrôle d’accès
- Seulement `admin` ou `is_owner && is_active`.

### Logique
- Validation.
- Ajoute `user_id`, `is_active = true`.
- Si ouvert 24h : remplace horaires/jours.
- Ajoute le créateur comme co-propriétaire.

---

## 📘 `show(Parking $parking)`

### Rôle
Affiche un parking spécifique.

### Contrôle d’accès
- Admin, créateur ou co-propriétaires.

### Logique
- Charge les co-propriétaires.
- Retourne un JSON formaté.

---

## 📘 `update(Request $request, Parking $parking)`

### Rôle
Met à jour un parking.

### Contrôle d’accès
- Admin ou créateur.

### Logique
- Validation.
- Si `user_email` fourni → transfert de propriété (transaction).
- `is_active = false` → désactive tous les spots.
- `is_active = true` → réactive tous les spots si autorisé.

---

## 📘 `destroy(Parking $parking)`

### Rôle
Supprime logiquement un parking.

### Contrôle d’accès
- Admin ou créateur.

### Logique
- Désactive le parking et ses spots.

---

## 🛠️ Méthodes privées

### `processOpeningDaysAndHours(Request $request)`
- Si ouvert 24h : jours = 1–7, heures = 00:00–23:59.
- Étend `"1-5"` en `"1,2,3,4,5"`.

### `validationRules(bool $isUpdate = false)`
- Règles dynamiques pour création/mise à jour.

### `deactivateParkingAndSpots(Parking $parking)`
- Désactive le parking + spots liés.

### `formatUserInfo(User $user)`
- Retourne nom complet + email.

### `formatParkingWithCoOwners(Parking $parking)`
- Retourne les infos du parking et des co-propriétaires.

</details>