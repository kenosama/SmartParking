


# 🗄️ Database Schema

<details open>
<summary>🇬🇧 English Version</summary>

## Overview

This document describes the structure of the database used in the SmartParking application. It summarizes each table created via Laravel migrations and their respective fields, types, constraints, and relationships.

---


## 📄 users

| Field              | Type      | Details                                 |
|-------------------|-----------|-----------------------------------------|
| id                | BIGINT    | Primary key                             |
| first_name        | STRING    | First name of the user                  |
| last_name         | STRING    | Last name of the user                   |
| email             | STRING    | Unique, required                        |
| email_verified_at | TIMESTAMP | Nullable                                |
| password          | STRING    | Hashed password                         |
| is_admin          | BOOLEAN   | Admin flag (default: false)             |
| is_owner          | BOOLEAN   | Owner flag (default: false)             |
| is_tenant         | BOOLEAN   | Tenant flag (default: true)             |
| is_active         | BOOLEAN   | If account is active (default: true)    |
| remember_token    | STRING    | "Remember me" token                     |
| created_at / updated_at | TIMESTAMPS | Auto-managed                 |

---

## 📄 parkings

| Field             | Type      | Details                                      |
|------------------|-----------|----------------------------------------------|
| id               | BIGINT    | Primary key                                  |
| name             | STRING    | Name of the parking                          |
| street           | STRING    | Street name                                  |
| location_number  | STRING    | Street number                                |
| zip_code         | STRING    | Postal code                                  |
| city             | STRING    | City                                          |
| country          | STRING    | Country                                       |
| total_capacity   | INTEGER   | Maximum number of spots                       |
| is_open_24h      | BOOLEAN   | Default: true                                 |
| opening_hours    | STRING    | Nullable                                      |
| opening_days     | STRING    | Nullable                                      |
| user_id          | FK → users.id | Owner of the parking                      |
| is_active        | BOOLEAN   | Active status                                 |
| created_at / updated_at | TIMESTAMPS | Auto-managed                        |

**Constraints:**
- Unique on `(street, location_number, zip_code, city)`

---

## 📄 parking_spots

| Field               | Type      | Details                                     |
|--------------------|-----------|---------------------------------------------|
| id                 | BIGINT    | Primary key                                 |
| parking_id         | FK → parkings.id | Belongs to a parking                   |
| user_id            | FK → users.id | Owner of the spot                        |
| identifier         | STRING    | e.g., A1, B2                                 |
| allow_electric_charge | BOOLEAN | Default: false                             |
| is_available       | BOOLEAN   | Default: true                                |
| is_booked          | BOOLEAN   | Default: false                               |
| per_day_only       | BOOLEAN   | Indicates if hourly booking is disabled      |
| price_per_day      | DECIMAL   | Default: 99                                  |
| price_per_hour     | DECIMAL   | Default: 3.5                                 |
| note               | TEXT      | Optional                                     |
| created_at / updated_at | TIMESTAMPS | Auto-managed                        |

**Constraints:**
- Unique on `(parking_id, identifier)`

---

## 📄 reservations

| Field             | Type      | Details                                     |
|------------------|-----------|---------------------------------------------|
| id               | BIGINT    | Primary key                                 |
| user_id          | FK → users.id | Booker                                  |
| parking_id       | FK → parkings.id | Parking context                     |
| parking_spot_id  | FK → parking_spots.id | Booked spot                  |
| start_datetime   | TIMESTAMP | Start of reservation                         |
| end_datetime     | TIMESTAMP | End of reservation                           |
| license_plate    | STRING    | Optional                                     |
| status           | ENUM      | See status list below                        |
| group_token      | UUID      | Optional, indexed                            |
| created_at / updated_at | TIMESTAMPS | Auto-managed                        |

**Status ENUM values:**
- `active`, `cancelled_by_user`, `cancelled_by_owner`, `cancelled_by_admin`, `manual_override`, `done`

**Constraints:**
- Unique on `(parking_spot_id, start_datetime, end_datetime, status)`

---

## 📄 parking_owner

| Field        | Type      | Details                          |
|-------------|-----------|----------------------------------|
| id          | BIGINT    | Primary key                      |
| parking_id  | FK → parkings.id | Parking                    |
| user_id     | FK → users.id | Co-owner user               |
| role        | ENUM      | Only: `co_owner`                 |
| created_at / updated_at | TIMESTAMPS | Auto-managed     |

**Constraints:**
- Unique on `(parking_id, user_id)`

---

## 📄 parking_transfers

| Field         | Type      | Details                          |
|--------------|-----------|----------------------------------|
| id           | BIGINT    | Primary key                      |
| parking_id   | FK → parkings.id | Target parking           |
| old_user_id  | FK → users.id | Previous owner             |
| new_user_id  | FK → users.id | New owner                  |
| performed_by | FK → users.id | Who triggered the transfer |
| created_at / updated_at | TIMESTAMPS | Auto-managed     |

</details>

---

<details>
<summary>🇫🇷 Version française</summary>

## Vue d’ensemble

Ce document décrit la structure de la base de données utilisée dans l’application SmartParking. Il résume chaque table créée via les migrations Laravel, avec les champs, types, contraintes et relations.

---


## 📄 users (utilisateurs)

| Champ              | Type      | Détails                                 |
|-------------------|-----------|-----------------------------------------|
| id                | BIGINT    | Clé primaire                            |
| first_name        | STRING    | Prénom de l’utilisateur                 |
| last_name         | STRING    | Nom de l’utilisateur                    |
| email             | STRING    | Email unique et requis                  |
| email_verified_at | TIMESTAMP | Nullable                                |
| password          | STRING    | Mot de passe hashé                      |
| is_admin          | BOOLEAN   | Admin (par défaut : false)              |
| is_owner          | BOOLEAN   | Propriétaire (par défaut : false)       |
| is_tenant         | BOOLEAN   | Locataire (par défaut : true)           |
| is_active         | BOOLEAN   | Compte actif (par défaut : true)        |
| remember_token    | STRING    | Token "remember me"                     |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement         |

---

## 📄 parkings

| Champ             | Type      | Détails                                      |
|------------------|-----------|----------------------------------------------|
| id               | BIGINT    | Clé primaire                                 |
| name             | STRING    | Nom du parking                               |
| street           | STRING    | Rue                                           |
| location_number  | STRING    | Numéro dans la rue                           |
| zip_code         | STRING    | Code postal                                   |
| city             | STRING    | Ville                                        |
| country          | STRING    | Pays                                         |
| total_capacity   | INTEGER   | Nombre maximal de places                     |
| is_open_24h      | BOOLEAN   | Par défaut : true                            |
| opening_hours    | STRING    | Nullable                                     |
| opening_days     | STRING    | Nullable                                     |
| user_id          | FK → users.id | Propriétaire                          |
| is_active        | BOOLEAN   | Statut actif                                 |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement         |

**Contraintes :**
- Unique sur `(street, location_number, zip_code, city)`

---

## 📄 parking_spots (places)

| Champ               | Type      | Détails                                     |
|--------------------|-----------|---------------------------------------------|
| id                 | BIGINT    | Clé primaire                                |
| parking_id         | FK → parkings.id | Appartient à un parking             |
| user_id            | FK → users.id | Propriétaire                             |
| identifier         | STRING    | e.g. A1, B2                                  |
| allow_electric_charge | BOOLEAN | Par défaut : false                         |
| is_available       | BOOLEAN   | Par défaut : true                            |
| is_booked          | BOOLEAN   | Par défaut : false                           |
| per_day_only       | BOOLEAN   | Réservable uniquement à la journée ?         |
| price_per_day      | DECIMAL   | Par défaut : 99                              |
| price_per_hour     | DECIMAL   | Par défaut : 3.5                             |
| note               | TEXT      | Optionnel                                   |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement         |

**Contraintes :**
- Unique sur `(parking_id, identifier)`

---

## 📄 reservations (réservations)

| Champ             | Type      | Détails                                     |
|------------------|-----------|---------------------------------------------|
| id               | BIGINT    | Clé primaire                                |
| user_id          | FK → users.id | Utilisateur                               |
| parking_id       | FK → parkings.id | Parking de référence                 |
| parking_spot_id  | FK → parking_spots.id | Place réservée              |
| start_datetime   | TIMESTAMP | Début de la réservation                      |
| end_datetime     | TIMESTAMP | Fin de la réservation                        |
| license_plate    | STRING    | Optionnel                                    |
| status           | ENUM      | Voir valeurs ci-dessous                      |
| group_token      | UUID      | Optionnel, indexé                            |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement         |

**Valeurs ENUM `status` :**
- `active`, `cancelled_by_user`, `cancelled_by_owner`, `cancelled_by_admin`, `manual_override`, `done`

**Contraintes :**
- Unique sur `(parking_spot_id, start_datetime, end_datetime, status)`

---

## 📄 parking_owner (copropriété)

| Champ        | Type      | Détails                          |
|-------------|-----------|----------------------------------|
| id          | BIGINT    | Clé primaire                     |
| parking_id  | FK → parkings.id | Parking concerné          |
| user_id     | FK → users.id | Copropriétaire              |
| role        | ENUM      | Valeur : `co_owner`              |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement |

**Contraintes :**
- Unique sur `(parking_id, user_id)`

---

## 📄 parking_transfers (transferts)

| Champ         | Type      | Détails                           |
|--------------|-----------|-----------------------------------|
| id           | BIGINT    | Clé primaire                      |
| parking_id   | FK → parkings.id | Parking transféré         |
| old_user_id  | FK → users.id | Ancien propriétaire           |
| new_user_id  | FK → users.id | Nouveau propriétaire          |
| performed_by | FK → users.id | Auteur du transfert          |
| created_at / updated_at | TIMESTAMPS | Gérés automatiquement |

</details>