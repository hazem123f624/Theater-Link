# Phase 4 — Theater Profile, Halls, Schedules & Events

This folder tracks the implementation and verification work for Phase 4 of TheaterLink.

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
| Theater profile | Planned | Tenant foundation and roles exist; profile module is next. |
| Hall management | Planned | No hall implementation exists in the reference. |
| Weekly schedules | Planned | Materialized slot generator is planned. |
| Slot blocking | Planned | Maintenance/private-use blocks. |
| Events and translations | Planned | Events, shows, posters, publish/unpublish. |
| Ticket tiers | Planned | Per-show tiers and capacity validation. |
| Public catalog read models | Planned | Published-only projections. |
| Theater dashboard | Planned | Tenant-scoped counts and operations. |

## Planned Endpoints

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
| `POST` | `/theater/dashboard/halls/{hall}/blocks` | Block a time period. |
| `GET` | `/theater/dashboard/events` | List events. |
| `POST` | `/theater/dashboard/events` | Create an event. |
| `PUT` | `/theater/dashboard/events/{event}` | Update an event. |
| `POST` | `/theater/dashboard/events/{event}/publish` | Publish an event. |
| `POST` | `/theater/dashboard/events/{event}/unpublish` | Unpublish an event. |
| `POST` | `/theater/dashboard/events/{event}/cancel` | Cancel an event. |
| `GET` | `/theater/dashboard/events/{event}/shows` | List shows. |
| `POST` | `/theater/dashboard/events/{event}/shows` | Create a show. |
| `GET` | `/theater/dashboard/shows/{show}/tiers` | List ticket tiers. |
| `POST` | `/theater/dashboard/shows/{show}/tiers` | Create a tier. |
| `PUT` | `/theater/dashboard/shows/{show}/tiers/{tier}` | Update a tier. |
| `DELETE` | `/theater/dashboard/shows/{show}/tiers/{tier}` | Deactivate a tier. |

### Public catalog

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/public/theaters` | List active theaters. |
| `GET` | `/public/theaters/{id}` | Public theater profile. |
| `GET` | `/public/theaters/{id}/halls` | Active public halls. |
| `GET` | `/public/halls/{id}/availability` | Requestable slots. |
| `GET` | `/public/events` | Published events. |
| `GET` | `/public/events/{slug}` | Published event details. |

## Known Gaps

1. No hall, schedule, event, show, or ticket-tier implementation exists yet.
2. The slot generator, event-show occupancy, and hall-block interactions must be implemented together.
3. Public projections must not expose private theater data.
