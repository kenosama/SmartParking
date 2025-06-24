# 🚗 SmartParking - Laravel Backend API

SmartParking is a RESTful API developed with Laravel 11 to manage shared parkings, parking spots, reservations, and users (with roles).

This project was created as part of a job application for a **Junior Back-End Developer** role, to demonstrate my practical skills in API development, database handling, and Laravel best practices.

---

## 🧠 Main Features

### 🔐 Authentication (Sanctum)
- Registration, login, logout
- Middleware for protected routes
- Secure session token

### 👤 User Management
- Full CRUD
- Roles: `user` / `admin`
- Soft delete via `is_active` field
- Profile update (email, password…)
- Admin can reactivate users

### 🅿️ Parking Management
- Create, read, update, delete
- Each parking has a user owner

### 📍 Parking Spot Management
- On-the-fly creation: `A1-A5, B1, B3`
- Search by country / zip code / parking
- Activation / deactivation (`is_available`)
- Belongs to a parking and an owner

### 📅 Smart Reservations
- Time slot validation
- Multi-spot support (comma-separated)
- Cleaned license plates stored
- Soft delete rules:
  - Admin: anytime
  - Owner: up to 48h before
  - User: up to 24h before
- Automatic status change to done after reservation ends

---

## 📁 API Documentation Structure

API docs are located in the [`docs/`](./docs) folder:

```
docs/
├── authentication.md
├── users.md
├── parkings.md
├── parkingspots.md
├── reservations.md
```

You can [browse the docs here](./docs).

---

## 🚀 Project Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-name/smartparking.git
cd smartparking

# 2. Install dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Configure your database in the .env file, then run:
php artisan migrate:fresh --seed

# 5. Start the local server
php artisan serve
```

---

## 📦 Tech Stack

- PHP 8.3+
- Laravel 11
- Laravel Sanctum
- MySQL
- Eloquent ORM
- Postman / REST Client
- Git + GitHub
- VS Code (Intelephense)

---

## 🔭 Roadmap

- [x] Sanctum authentication
- [x] Full CRUD: User, Parking, Spot, Reservation
- [x] Soft deletes + custom statuses
- [x] Strong business rules on reservations
- [x] Markdown-based API documentation
- [x] Spot availability search/filter
- [x] Role/delay protected actions
- [x] API versioning ready
- [ ] Create a visual interface for the whole site using Blade, React, or Vue.
---

## 📄 License

This project is freely usable for personal or educational purposes.

---

## ✉️ Contact

If you have any questions or feedback, feel free to contact me via LinkedIn or open a GitHub issue.

---

## 🧪 API Versioning Preparation

All current routes are in `routes/api.php`.  
Example to enable v1 versioning easily:

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('/parkings', ParkingController::class);
    // other routes here...
});
```

---

<details closed>
<summary>🇫🇷 Lire en français</summary>

## 📁 Structure de la documentation

La documentation API se trouve dans le dossier [`docs/`](./docs) :

```
docs/
├── authentication.md
├── users.md
├── parkings.md
├── parkingspots.md
├── reservations.md
```

Vous pouvez [parcourir les documents ici](./docs).
```

---

## 🧠 Fonctionnalités principales

### 🔐 Authentification (Sanctum)
- Inscription, connexion, déconnexion
- Middleware pour routes protégées
- Token de session sécurisé

### 👤 Gestion des utilisateurs
- CRUD utilisateurs
- Rôles : `user` / `admin`
- Soft delete via champ `is_active`
- Mise à jour profil (email, password...)
- Réactivation possible par admin

### 🅿️ Gestion des parkings
- Création, lecture, mise à jour, suppression
- Chaque parking a un propriétaire (user)

### 📍 Gestion des places (parking spots)
- Création dynamique des emplacements : `A1-A5, B1, B3`
- Recherche par pays / code postal / parking
- Activation / désactivation (`is_available`)
- Attribution à un parking et à un propriétaire

### 📅 Réservations intelligentes
- Créneaux horaires avec validation
- Multi-emplacements (séparés par des virgules)
- Plaques d’immatriculation nettoyées et stockées
- Soft delete (annulation) selon règles :
  - Admin : à tout moment
  - Propriétaire : jusqu’à 48h avant
  - Utilisateur : jusqu’à 24h avant
- Passage automatique à `done` si date expirée

---

## 🚀 Installation du projet

```bash
# 1. Cloner le dépôt
git clone https://github.com/votre-nom/smartparking.git
cd smartparking

# 2. Installer les dépendances
composer install

# 3. Configurer l’environnement
cp .env.example .env
php artisan key:generate

# 4. Définir la base de données dans .env
# Puis exécuter les migrations
php artisan migrate:fresh --seed

# 5. Lancer le serveur local
php artisan serve
```

---

## 📦 Stack technique

- PHP 8.3+
- Laravel 11
- Laravel Sanctum
- MySQL
- Eloquent ORM
- Postman / REST Client
- Git + GitHub
- VS Code (Intelephense)

---

## 🔭 Roadmap réalisée

- [x] Authentification Sanctum
- [x] Gestion CRUD des entités : User, Parking, Spot, Réservation
- [x] Soft deletes + statuts personnalisés
- [x] Règles métiers fortes sur réservations
- [x] Documentation en fichiers Markdown
- [x] Recherche et filtrage de spots disponibles
- [x] Protection des actions sensibles (rôles, délais)
- [x] Préparation au versionnement API
- [ ] Créer une interface visuelle du site via Blade, React ou Vue.

---

## 📄 Licence

Ce projet est librement utilisable à des fins personnelles ou éducatives.

---

## ✉️ Contact

Si vous avez des questions ou remarques, n’hésitez pas à me contacter via LinkedIn ou à ouvrir une issue GitHub.

---

## 🧪 Préparation au versionnement futur

Toutes les routes actuelles sont centralisées dans `routes/api.php`.  
Voici un exemple pour activer facilement une version v1 :

```php
Route::prefix('v1')->group(function () {
    Route::apiResource('/parkings', ParkingController::class);
    // autres routes ici...
});
```
</details>