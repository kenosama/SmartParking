# 🚗 SmartParking - Laravel Backend API

SmartParking is a RESTful backend API developed in Laravel 11 that allows users to register and reserve shared parking spots, while parking owners and administrators can manage parkings, spots, reservations, and users.

This project was built as part of a **Junior Backend Developer** application, aiming to showcase clean architecture, API logic, and Laravel 11 best practices.

---

## 🧠 Main Features

### 🔐 Authentication
- Laravel Sanctum
- Registration / Login / Logout
- Session token management
- Middleware-protected routes

### 👤 User Management
- CRUD for users
- Roles: `admin`, `user`
- Soft-delete via `is_active`
- Admin can restore and re-enable accounts
- Profile editing: email, password

### 🅿️ Parking Management
- Each parking belongs to a user (owner)
- Co-owners support
- CRUD (Create, Read, Update, Delete)
- Includes address and availability options
- Opening hours and days

### 📍 Parking Spot Management
- ParkingSpot belongs to a Parking and a User
- On-the-fly creation: `"A1-A5, B1, B3"`
- Spot availability toggles
- Search by country, zip code, parking ID
- Electric charger support
- Hourly or daily pricing

### 📅 Reservation System
- Create reservation for one or multiple spots
- Smart scheduling and availability validation
- License plate normalization
- Cancelation and soft-delete logic
  - Admin: anytime
  - Owner: up to 48h before
  - User: up to 24h before
- Auto-expiration to `done` status
- Grouped reservation listing by status

---

## 📂 Documentation

The documentation is located in the [`docs/`](./docs) folder and is **fully bilingual** 🇬🇧/🇫🇷.

```
docs/
├── API ref/
│   ├── UserController.md
│   ├── ParkingController.md
│   ├── ParkingOwnerController.md
│   ├── ParkingSpotController.md
│   ├── ReservationController.md
│   ├── SmartParking.postman_collection.json
│   └── APIroutes.md
├── controllers/
│   ├── UserController.md
│   ├── ParkingController.md
│   ├── ParkingOwnerController.md
│   ├── ParkingSpotController.md
│   ├── ReservationController.md
├── structure/
│   ├── models.md
│   ├── relations.md
│   ├── database.md
│   └── erd.md
```

You can browse the documentation in full [here](./docs).

---

## 📬 Postman Collection

A fully documented Postman collection is included to test all API routes easily.

You can import the file `SmartParking.postman_collection.json` into Postman.

---

## 🚀 Installation Guide

```bash
# 1. Clone the repo
git clone https://github.com/kenosama/SmartParking.git
cd SmartParking

# 2. Install PHP dependencies
composer install

# 3. Create environment file
cp .env.example .env
php artisan key:generate

# 4. Set up your database credentials in .env
php artisan migrate:fresh --seed

# 5. Launch development server
php artisan serve
```

---
## 🛠️ Tech Stack

- PHP 8.3+
- Laravel 11
- Laravel Sanctum
- MySQL
- Eloquent ORM
- Postman / REST Client (VS Code)
- Git + GitHub
- VS Code (with Intelephense)

---

## 📄 License

This project is open source and freely usable for learning or portfolio purposes.

---

## ✉️ Contact

Feel free to connect with me on [LinkedIn](https://www.linkedin.com/in/thomas-cano-morant/) or open a GitHub issue if you have questions.

---

<details>
<summary>🇫🇷 Version française</summary>

# 🚗 SmartParking - API Laravel Backend

SmartParking est une API backend RESTful développée en Laravel 11 permettant aux utilisateurs de réserver des places de parking partagées, et aux propriétaires / administrateurs de gérer parkings, emplacements, réservations et utilisateurs.

Ce projet a été réalisé dans le cadre d’une **candidature pour un poste de développeur Backend junior**, afin de démontrer une architecture claire et des compétences Laravel modernes.

---

## 🧠 Fonctionnalités principales

### 🔐 Authentification
- Laravel Sanctum
- Inscription / Connexion / Déconnexion
- Token de session sécurisé
- Middleware pour protéger les routes

### 👤 Gestion des utilisateurs
- CRUD utilisateur
- Rôles : `admin`, `user`
- Soft-delete avec champ `is_active`
- Réactivation possible par un administrateur
- Modification du profil (email, mot de passe)

### 🅿️ Gestion des parkings
- Chaque parking appartient à un utilisateur (propriétaire)
- Support des copropriétaires
- CRUD complet
- Adresse, disponibilité, horaires

### 📍 Gestion des emplacements (spots)
- Lien entre spot, parking et utilisateur
- Création à la volée via : `"A1-A5, B1, B3"`
- Activation / désactivation
- Recherche par pays / code postal / parking
- Support pour bornes électriques
- Tarification horaire ou journalière

### 📅 Réservations
- Réservation multi-spot avec validation de créneau
- Normalisation des plaques
- Annulation / suppression logique selon rôle :
  - Admin : à tout moment
  - Propriétaire : jusqu’à 48h avant
  - Utilisateur : jusqu’à 24h avant
- Passage automatique à l’état `done` si expirée
- Groupement des réservations par statut

---

## 📂 Documentation

La documentation complète se trouve dans [`docs/`](./docs) et est **entièrement bilingue** 🇬🇧/🇫🇷.

```
docs/
├── API ref/
│   ├── UserController.md
│   ├── ParkingController.md
│   ├── ParkingOwnerController.md
│   ├── ParkingSpotController.md
│   ├── ReservationController.md
│   ├── SmartParking.postman_collection.json
│   └── APIroutes.md
├── controllers/
│   ├── UserController.md
│   ├── ParkingController.md
│   ├── ParkingOwnerController.md
│   ├── ParkingSpotController.md
│   ├── ReservationController.md
├── structure/
│   ├── models.md
│   ├── relations.md
│   ├── database.md
│   └── erd.md
```

Vous pouvez explorer toute la documentation [ici](./docs).

---
## 📬 Collection Postman

Une collection Postman entièrement documentée est incluse pour tester facilement toutes les routes de l’API.

Vous pouvez importer le fichier `SmartParking.postman_collection.json` dans Postman.

---

## 🚀 Guide d’installation

```bash
# 1. Cloner le dépôt
git clone https://github.com/kenosama/SmartParking.git
cd SmartParking

# 2. Installer les dépendances
composer install

# 3. Créer le fichier .env
cp .env.example .env
php artisan key:generate

# 4. Configurer les infos de base de données
php artisan migrate:fresh --seed

# 5. Lancer le serveur de développement
php artisan serve
```


## 🛠️ Stack technique

- PHP 8.3+
- Laravel 11
- Laravel Sanctum
- MySQL
- Eloquent ORM
- Postman / REST Client (VS Code)
- Git + GitHub
- VS Code (Intelephense)

---

## 📄 Licence

Ce projet est libre et réutilisable à des fins d’apprentissage ou de portfolio.

---

## ✉️ Contact

N’hésitez pas à me contacter sur [LinkedIn](https://www.linkedin.com/in/thomas-cano-morant/) ou à ouvrir une issue GitHub.

---

</details>