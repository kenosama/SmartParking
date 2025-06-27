

# 🛣️ Roadmap – SmartParking Project

This document lists pending or upcoming tasks for the SmartParking API (Laravel 11). It complements the feature documentation and helps track remaining improvements, bugs and refactors.

---

## ✅ Completed Features

- CRUD operations for Parkings and Parking Spots.
- RBAC with Laravel Sanctum: Admin, Owner, Co-owner.
- Spot creation via identifier ranges (`A1-A5, B1`).
- Grouping of spots by co-owner in JSON output.
- Search endpoint by `parking_id` and `zipcode`.
- Price sorting and availability filters.
- `formatSpotResponse()` method for consistent API formatting.
- Syncing `parking_owner` pivot table for spots during transfer.
- Custom error handling (403 for unauthorized access).
- Pretty printed JSON responses with `JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT`.

---

## 🚧 To Do

### 🔐 Access & Roles

- [ ] Prevent duplicate `identifier` values per parking (including updates).
- [ ] Add automated tests for RBAC (co-owner vs owner vs admin).
- [ ] Add middleware or policy checks for reservations per user role.

### 🧠 Models & Logic

- [X] Auto-add parking creator as co-owner in `parking_owner` table.
- [X] When transferring parking ownership:
  - [X] Replace owner ID in pivot table (spots and co-owners).
  - [X] Create `ParkingTransfer` log model.
  - [X] Store actor ID (`performed_by`) for audit trail.

### 📦 Refactoring

- [ ] Move validation rules to dedicated `FormRequest` classes.
- [ ] Extract logic into Services for complex controllers (e.g. Spot assignment).
- [ ] Refactor shared JSON formatting methods to dedicated Resource classes or helpers.

### 📚 Documentation

- [ ] Finalize `ParkingSpotController.md` (bilingual with collapsible FR/EN).
- [ ] Auto-generate full API reference (OpenAPI, Postman, Markdown).
- [ ] Add markdown for `ParkingTransfer.md` explaining audit purpose and structure.

---

## 🧪 Tests

- [ ] PHPUnit: tests for `store`, `update`, `search` endpoints.
- [ ] Integration test for transfer + pivot update.
- [ ] Spot duplication protection on update.

---

## 🎯 Later / Nice to Have

- [ ] Scheduling module for reservations.
- [ ] Notifications (email/SMS) for owners.
- [ ] Export of parking usage stats (CSV, JSON).