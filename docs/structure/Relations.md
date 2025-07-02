


# 🔗 Model Relationships

<details open>
<summary>🇬🇧 English Version</summary>

## Overview

This document describes the logical and business relationships between models in the SmartParking project. It complements the technical relations found in `models.md` by detailing how data interactions should behave in real-world usage.

---

## 🧩 User ↔ Parking

- A user can own multiple parkings (`hasMany`).
- A user can also be a co-owner of parkings via a many-to-many relationship.
- Only the main owner (not co-owners) can transfer ownership or modify parking-wide settings.

**Business rule:** Co-owners may access shared data but cannot modify global settings or delete the parking.

---

## 🧩 Parking ↔ ParkingSpot

- A parking has many parking spots.
- Each spot must belong to exactly one parking (`belongsTo`).
- The total number of active spots should not exceed `total_capacity`.

**Business rule:** Deactivating a parking makes all its spots unavailable.

---

## 🧩 User ↔ ParkingSpot

- A user can own/manage multiple spots.
- A spot belongs to one user (can be a co-owner or not).
- The owner of a spot may define its pricing and availability.

**Business rule:** A user cannot own spots in a parking they don’t belong to (as main owner or co-owner).

---

## 🧩 ParkingSpot ↔ Reservation

- A spot can have multiple reservations.
- A reservation must belong to one spot.

**Business rule:** A spot cannot be double-booked in overlapping time slots.

---

## 🧩 User ↔ Reservation

- A user can create many reservations.
- Reservations must be made in the future.
- A user cannot have two active reservations for the same time window.

---

## 🧩 ParkingTransfert

- Tracks historical ownership transfers.
- Involves: parking, old owner, new owner, and action initiator.
- Only admins or the current owner may perform a transfer.

**Business rule:** After transfer, the new owner replaces the old one in the `user_id` field of the Parking. Co-owners remain unchanged.

</details>

---

<details>
<summary>🇫🇷 Version française</summary>

## Vue d’ensemble

Ce document décrit les relations logiques et métiers entre les modèles de l’application SmartParking. Il complète le fichier `models.md` en apportant des précisions sur les règles de gestion et les comportements attendus entre entités.

---

## 🧩 User ↔ Parking

- Un utilisateur peut posséder plusieurs parkings (`hasMany`).
- Il peut aussi être copropriétaire via une relation many-to-many.
- Seul le propriétaire principal peut transférer la propriété ou modifier les paramètres globaux.

**Règle métier :** Les copropriétaires ont accès aux données partagées mais ne peuvent pas modifier les réglages du parking ni le supprimer.

---

## 🧩 Parking ↔ ParkingSpot

- Un parking contient plusieurs places.
- Chaque place appartient à un seul parking (`belongsTo`).
- Le nombre de places actives ne doit pas dépasser la `total_capacity`.

**Règle métier :** Désactiver un parking rend toutes ses places inaccessibles.

---

## 🧩 User ↔ ParkingSpot

- Un utilisateur peut gérer plusieurs places.
- Une place est rattachée à un utilisateur unique (copropriétaire ou non).
- Le propriétaire définit la disponibilité et les prix.

**Règle métier :** Un utilisateur ne peut gérer des places que dans les parkings auxquels il est lié (comme propriétaire ou copropriétaire).

---

## 🧩 ParkingSpot ↔ Reservation

- Une place peut être réservée plusieurs fois.
- Chaque réservation est liée à une seule place.

**Règle métier :** Une place ne peut pas être réservée deux fois pour des périodes qui se chevauchent.

---

## 🧩 User ↔ Reservation

- Un utilisateur peut effectuer plusieurs réservations.
- Les réservations doivent porter sur des dates futures.
- Un utilisateur ne peut pas avoir deux réservations actives au même moment.

---

## 🧩 ParkingTransfert

- Suivi des changements de propriétaire.
- Implique : le parking, l’ancien propriétaire, le nouveau, et l’auteur de l’action.
- Seuls les administrateurs ou le propriétaire actuel peuvent initier un transfert.

**Règle métier :** Après le transfert, le champ `user_id` du parking est mis à jour. Les copropriétaires ne sont pas modifiés.

</details>