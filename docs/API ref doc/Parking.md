# 📘 API Documentation - Parkings

<details open>
<summary>🇬🇧 English Version</summary>

This documentation covers the available routes for managing **parkings** in the Laravel API.

---

## 🔐 All routes require authentication via Bearer token.

---

## 🔄 List Parkings

- **Method**: `GET`
- **URL**: `/api/parkings`
- **Access**:
  - Admin: all parkings in the system grouped by creator, with co-owner info.
  - Creator: only the parkings the user has created.
  - Co-owner: only parkings where the user is listed as co-owner.
  - All others: `403 Unauthorized`
- **Note**: This endpoint does not return parking spots.
- **Response**: List of parkings per role with co-owner details.

---

## 📄 Parking Details

- **Method**: `GET`
- **URL**: `/api/parkings/{id}`
- **Access**:
  - Admin
  - Creator
  - Co-owner
  - Others: 403
- **Response**: Full parking details including co-owners.

---

## ➕ Create a new Parking

- **Method**: `POST`
- **URL**: `/api/parkings`
- **Access**:
  - Admins
  - Users with `is_owner = true` and `is_active = true`
- **Required fields**:
```json
{
  "name": "Parking Nord",
  "street": "Rue des Lilas",
  "location_number": "123",
  "zip_code": "1000",
  "city": "Brussels",
  "country": "Belgium",
  "total_capacity": 20,
  "is_open_24h": true,
  "opening_hours": null,
  "opening_days": null
}
```



📝 Update a Parking
	•	Method: PUT
	•	URL: /api/parkings/{id}
	•	Access:
	•	Admins
	•	Creator only
	•	Note: Cannot be updated by co-owners.
	•	Updatable fields: any of the creation fields + is_active

⸻

❌ Delete a Parking
	•	Method: DELETE
	•	URL: /api/parkings/{id}
	•	Access:
	•	Admins
	•	Creator
	•	Behavior: Performs a soft delete (is_active = false) and disables all parking spots.

</details>



⸻


<details>
<summary>🇫🇷 Version Française</summary>


Cette documentation couvre les routes disponibles pour la gestion des parkings dans l’API Laravel.

⸻

🔐 Toutes les routes nécessitent une authentification via token Bearer.

⸻

🔄 Liste des parkings
	•	Méthode : GET
	•	URL : /api/parkings
	•	Accès :
	•	Admin : tous les parkings du système groupés par créateur avec les co-propriétaires
	•	Créateur : uniquement les parkings créés
	•	Co-propriétaire : uniquement les parkings dans lesquels il est listé
	•	Autres utilisateurs : 403 Unauthorized
	•	Remarque : Cette route ne retourne pas les places (spots).
	•	Réponse : liste structurée des parkings selon le rôle

⸻

📄 Détail d’un parking
	•	Méthode : GET
	•	URL : /api/parkings/{id}
	•	Accès :
	•	Admin
	•	Créateur
	•	Co-propriétaire
	•	Autres : erreur 403
	•	Réponse : Détails complets du parking avec co-propriétaires.

⸻

➕ Créer un nouveau parking
	•	Méthode : POST
	•	URL : /api/parkings
	•	Accès :
	•	Admin
	•	Utilisateurs avec is_owner = true et is_active = true
	•	Champs requis :

{
  "name": "Parking Nord",
  "street": "Rue des Lilas",
  "location_number": "123",
  "zip_code": "1000",
  "city": "Bruxelles",
  "country": "Belgique",
  "total_capacity": 20,
  "is_open_24h": true,
  "opening_hours": null,
  "opening_days": null
}


⸻

📝 Modifier un parking
	•	Méthode : PUT
	•	URL : /api/parkings/{id}
	•	Accès :
	•	Admins
	•	Créateur uniquement
	•	Remarque : Les co-propriétaires ne peuvent pas modifier un parking.
	•	Champs modifiables : tous les champs de création + is_active

⸻

❌ Supprimer un parking
	•	Méthode : DELETE
	•	URL : /api/parkings/{id}
	•	Accès :
	•	Admins
	•	Créateur
	•	Comportement : désactive le parking (is_active = false) et toutes ses places.

</details>
