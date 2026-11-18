# Phase 4 — Theater Profile, Halls, Schedules & Events

This folder tracks the implementation and verification work for Phase 4 of TheaterLink.

**Status:** Phase 4 backend and frontend are complete and verified.

**Goal:** an active theater can manage its profile, halls, weekly schedules, generated time slots, events, shows, and ticket tiers.

**Source of truth:** [`../business.md`](../business.md) — Phase 4.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 4. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Theater profile | Complete | Editable profile, location, timezone, logo, cover, and public projection. |
| Hall management | Complete | Tenant-scoped CRUD, capacity, active state, and future-show protection. |
| Weekly schedules | Complete | Validated weekly windows, idempotent materialized slots, and regeneration. |
| Slot blocking | Complete | Maintenance/private-use blocks with slot restoration. |
| Events and translations | Complete | English/Arabic content, posters, publish/unpublish/cancel. |
| Ticket tiers | Complete | Per-show tiers, sale windows, quantity, and capacity validation. |
| Public catalog read models | Complete | Active theaters, published events, public halls, and availability. |
| Theater dashboard | Complete | Real hall, event, published-event, and upcoming-show counts. |

## Endpoints

Base path: `/api/v1`

### Theater workspace

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/theater/dashboard/profile` | View the theater profile. |
| `PUT` | `/theater/dashboard/profile` | Update the theater profile. |
| `GET` | `/theater/dashboard/halls` | List halls. |
| `POST` | `/theater/dashboard/halls` | Create a hall. |
| `GET` | `/theater/dashboard/halls/{hall}` | View a hall. |
| `PUT` | `/theater/dashboard/halls/{hall}` | Update a hall. |
| `DELETE` | `/theater/dashboard/halls/{hall}` | Soft-delete a hall. |
| `GET` | `/theater/dashboard/halls/{hall}/schedules` | List weekly schedules. |
| `POST` | `/theater/dashboard/halls/{hall}/schedules` | Create a schedule. |
| `PUT` | `/theater/dashboard/halls/{hall}/schedules/{schedule}` | Update a schedule. |
| `DELETE` | `/theater/dashboard/halls/{hall}/schedules/{schedule}` | Remove a schedule. |
| `GET` | `/theater/dashboard/halls/{hall}/slots` | List generated slots. |
| `POST` | `/theater/dashboard/halls/{hall}/slots/generate` | Regenerate materialized slots. |
| `GET` | `/theater/dashboard/halls/{hall}/blocks` | List blocked periods. |
| `POST` | `/theater/dashboard/halls/{hall}/blocks` | Block a time period. |
| `DELETE` | `/theater/dashboard/blocks/{block}` | Remove a block. |
| `GET` | `/theater/dashboard/events` | List events. |
| `POST` | `/theater/dashboard/events` | Create an event. |
| `PUT` | `/theater/dashboard/events/{event}` | Update an event. |
| `POST` | `/theater/dashboard/events/{event}/publish` | Publish an event. |
| `POST` | `/theater/dashboard/events/{event}/unpublish` | Unpublish an event. |
| `POST` | `/theater/dashboard/events/{event}/cancel` | Cancel an event. |
| `GET` | `/theater/dashboard/events/{event}/shows` | List shows. |
| `POST` | `/theater/dashboard/events/{event}/shows` | Create a show. |
| `GET` | `/theater/dashboard/shows/{show}` | View a show. |
| `PUT` | `/theater/dashboard/shows/{show}` | Update/reschedule a show. |
| `POST` | `/theater/dashboard/shows/{show}/cancel` | Cancel a show. |
| `GET` | `/theater/dashboard/shows/{show}/tiers` | List ticket tiers. |
| `POST` | `/theater/dashboard/shows/{show}/tiers` | Create a tier. |
| `PUT` | `/theater/dashboard/tiers/{tier}` | Update a tier. |
| `DELETE` | `/theater/dashboard/tiers/{tier}` | Deactivate a tier. |

### Public catalog

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/public/theaters` | List active theaters. |
| `GET` | `/public/theaters/{id}` | Public theater profile. |
| `GET` | `/public/theaters/{id}/halls` | Active public halls. |
| `GET` | `/public/halls/{id}/availability` | Requestable slots. |
| `GET` | `/public/events` | Published events. |
| `GET` | `/public/events/{slug}` | Published event details. |
| `GET` | `/public/event-categories` | Active event categories. |

## Frontend

- Editable theater profile with logo and cover uploads.
- Hall list/detail pages with create/edit/delete and capacity display.
- Weekly schedule editor, generated slot list, date/status filters, manual regeneration, and maintenance blocks.
- Event list/detail pages with English/Arabic content, poster upload, publish/unpublish/cancel.
- Show creation/cancellation with hall and overlap feedback.
- Ticket tier management with price, quantities, sale windows, and per-booking limits.
- Public theater list/detail, public event list/detail, event categories, and availability views.
- English/Arabic labels and RTL support.

## Deferred

- Group discovery, reservations, holds, approval, and expiry jobs belong to Phase 5.
- Ticket booking, public discovery polish, inventory, and QR tickets belong to Phase 6.
- External payment and mobile clients remain out of scope.
