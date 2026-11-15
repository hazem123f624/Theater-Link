# Phase 1 — Architecture Analysis

This folder tracks the completed analysis phase for TheaterLink.

**Goal:** understand `qeei_backend_admin`, identify reusable foundations, evaluate multi-tenancy and concurrency strategies, and produce a source-backed architecture proposal before implementation.

**Source of truth:** [`../phase-1-technical-analysis.md`](../phase-1-technical-analysis.md).

## Files

| File | Purpose |
|---|---|
| [`../phase-1-technical-analysis.md`](../phase-1-technical-analysis.md) | Full technical analysis, including the three critical architecture decisions and the final decision table. |
| [`tasks.md`](tasks.md) | Analysis and decision checklist. |
| [`test-cases.md`](test-cases.md) | Architecture review checklist used to validate the proposal. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Reference project inspection | Complete | Shared services, auth, media, OTP, admin, and Valuer onboarding reviewed. |
| Multi-tenancy analysis | Complete | Shared database + row-level isolation is the real reference model; hybrid tenant architecture recommended. |
| Registration and approval blueprint | Complete | Valuer application/document workflow mapped to Theater Group onboarding. |
| Hall scheduling strategy | Complete | Temporary slot hold with expiry recommended. |
| Ticket inventory strategy | Complete | Immediate confirmation with atomic decrement recommended; hold-ready schema. |
| Implementation readiness | Complete | Phase 2 started with the recorded decisions; operational values remain configurable. |

## Key Decisions

| Topic | Decision |
|---|---|
| Tenant architecture | Hybrid: shared tenant/member/auth/RBAC foundation, separate theater/group domain modules. |
| Database isolation | Shared database with mandatory `tenant_id`. |
| Tenant type detection | Derived from the authenticated tenant member, never from client input. |
| Reservation conflicts | Materialized slots, row locks, unique constraints, and temporary holds. |
| Ticket inventory | Immediate confirmation and atomic decrement; optional hold mode later. |

## Known Gaps

1. The reference contains no hall/reservation/ticket domain implementation; those modules are new work.
2. The reference roles/permissions are not consistently enforced; TheaterLink enforces policies and middleware.
3. Historical physical-database tenant commands remain as dead legacy code in the reference and are not part of TheaterLink.
