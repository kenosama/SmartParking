<details open>
<summary>🇬🇧 English Version</summary>

## 📌 Public Routes

- `POST /register` → Register a new user  
- `POST /login` → Login and receive Sanctum token  
- `POST /logout` → Logout authenticated user  
- `GET /parking-spots/search` → Search for parking spots (by country, zip, etc.)

## 🔐 Protected Routes (auth:sanctum)

### Authenticated User
- `GET /me` → Get the current authenticated user

### 🚗 Main Resources
- `GET /parkings` → List all parkings  
- `POST /parkings` → Create a new parking  
- `GET /parkings/{id}` → Show a specific parking  
- `PUT /parkings/{id}` → Update a parking  
- `DELETE /parkings/{id}` → Delete a parking  

- Same CRUD for `/parking-spots` and `/reservations`

### 👥 Co-Owners
- `GET /parkings/{parking}/co-owners` → List co-owners  
- `POST /parkings/{parking}/co-owners` → Add co-owner  
- `DELETE /parkings/{parking}/co-owners` → Remove co-owner  

### 👤 User Management
- `GET /user/{identifier}` → Show user by ID or email  
- `PUT /user/{identifier}` → Update user  
- `DELETE /user/{identifier}` → Deactivate user  
- `PATCH /user/{identifier}/reactivate` → Reactivate user

</details>

<details>
<summary>🇫🇷 Version française</summary>

## 📌 Routes publiques

- `POST /register` → Enregistrer un nouvel utilisateur  
- `POST /login` → Se connecter et recevoir un token Sanctum  
- `POST /logout` → Se déconnecter (utilisateur authentifié)  
- `GET /parking-spots/search` → Rechercher des places (par pays, code postal, etc.)

## 🔐 Routes protégées (auth:sanctum)

### Utilisateur connecté
- `GET /me` → Obtenir les infos de l’utilisateur connecté

### 🚗 Ressources principales
- `GET /parkings` → Lister les parkings  
- `POST /parkings` → Créer un parking  
- `GET /parkings/{id}` → Afficher un parking  
- `PUT /parkings/{id}` → Mettre à jour un parking  
- `DELETE /parkings/{id}` → Supprimer un parking  

- CRUD identique pour `/parking-spots` et `/reservations`

### 👥 Co-propriétaires
- `GET /parkings/{parking}/co-owners` → Lister les co-propriétaires  
- `POST /parkings/{parking}/co-owners` → Ajouter un co-propriétaire  
- `DELETE /parkings/{parking}/co-owners` → Supprimer un co-propriétaire  

### 👤 Gestion des utilisateurs
- `GET /user/{identifier}` → Afficher un utilisateur (ID ou email)  
- `PUT /user/{identifier}` → Mettre à jour un utilisateur  
- `DELETE /user/{identifier}` → Désactiver un utilisateur  
- `PATCH /user/{identifier}/reactivate` → Réactiver un utilisateur

</details>
