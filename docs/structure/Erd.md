# 🗺️ Entity Relationship Diagram (ERD)

<details open>
<summary>🇬🇧 English Version</summary>
## Overview

This ERD provides a visual representation of the main database entities used in SmartParking and how they relate to each other. It is meant to guide developers in understanding the structure and logic behind the system's models and foreign key constraints.

## Mermaid ERD

```mermaid
erDiagram
    users ||--o{ parkings : owns
    users ||--o{ parking_spots : manages
    users ||--o{ reservations : books
    users }o--o{ parkings : co_owns
    users ||--o{ parking_transfers : performed
    users ||--o{ parking_transfers : old_owner
    users ||--o{ parking_transfers : new_owner

    parkings ||--o{ parking_spots : contains
    parkings ||--o{ reservations : has
    parkings ||--o{ parking_owner : has_coowners
    parkings ||--o{ parking_transfers : transfer_record

    parking_spots ||--o{ reservations : reserved_by

    reservations }o--|| users : booked_by
    reservations }o--|| parking_spots : for_spot
    reservations }o--|| parkings : in_parking

    parking_owner }o--|| users : co_owner
    parking_owner }o--|| parkings : linked_to

    parking_transfers }o--|| parkings : target
```

</details>

---

<details>
<summary>🇫🇷 Version française</summary>

## Vue d’ensemble

Ce diagramme ERD présente visuellement les principales entités de la base de données SmartParking et leurs relations. Il aide les développeurs à comprendre la structure des données et les contraintes entre les modèles via les clés étrangères.

## Diagramme Mermaid

```mermaid
erDiagram
    users ||--o{ parkings : possède
    users ||--o{ parking_spots : gère
    users ||--o{ reservations : réserve
    users }o--o{ parkings : copropriété
    users ||--o{ parking_transfers : initié_par
    users ||--o{ parking_transfers : ancien_proprio
    users ||--o{ parking_transfers : nouveau_proprio

    parkings ||--o{ parking_spots : contient
    parkings ||--o{ reservations : a_reservations
    parkings ||--o{ parking_owner : a_copropriétaires
    parkings ||--o{ parking_transfers : historique

    parking_spots ||--o{ reservations : réservée_par

    reservations }o--|| users : réservée_par
    reservations }o--|| parking_spots : pour_place
    reservations }o--|| parkings : dans_parking

    parking_owner }o--|| users : copropriétaire
    parking_owner }o--|| parkings : lié_à

    parking_transfers }o--|| parkings : concerne
```

</details>
