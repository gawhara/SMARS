# SMARS — Project Analysis

_Analysis date: 2026-07-06 · Laravel 12 · PHP 8.2 · MySQL (`smars`)_

SMARS is a Saudi-market HR/ERP system for one organization managing four companies
(AMNIAT, AMNIAT FACTORY, PTC, PTC Construction). It is being built **phase by phase**
per `CODEX.txt`. This document assesses what exists today, how well it matches the
CODEX specification, and what to fix before advancing.

---

## 1. Build status vs. CODEX phase plan

| Phase | Scope | Status |
|-------|-------|--------|
| 0 — Inspection & safety | Inspect project, detect auth/layout/db | ✅ Done |
| 1 — Foundation | Auth, layout, AR/EN + RTL/LTR, local Cairo font, language switch, dashboard shell, sidebar, 4-company seed, roles, settings, DB foundation | ✅ Done |
| 2 — Organization structure | Companies, company-scoped branches, global departments, global positions, shifts | ✅ Done — Shifts module added 2026-07-06 |
| 3 — Employee Management | Full employee schema, CODEX validation rules, banks/countries references, CRUD, tabbed profile | ✅ Done 2026-07-06 |
| 4–10 — Documents, attendance/biometric, leaves, payroll, RBAC/audit, reports | — | ⛔ Not started (expected) |

**Verdict:** Phases 1–3 are complete and tested. All 14 automated tests pass
(58 assertions). Employee create/edit/show views render; login is throttled; audit
authors are tracked across all org + employee tables.

---

## 2. What matches the spec well

- **Offline assets** — Cairo font (AR + Latin, weights 400–700) self-hosted under
  `public/build/assets/`, loaded via Vite. No CDN dependency. ✅ (CODEX §4–5)
- **Bilingual + RTL/LTR** — `SetLocale` middleware, `<html dir>` switch, session +
  user-profile persistence via `LanguageController`, 85 matched keys in `lang/ar` and
  `lang/en`. No hardcoded UI strings observed. ✅ (CODEX §6)
- **Locale persisted to user profile** — `LanguageController` saves `preferred_locale`
  when authenticated; login restores it. ✅ (CODEX §6)
- **Org data model** — branches are company-scoped (`company_id` FK,
  `restrictOnDelete`, unique `[company_id, name_ar/name_en]`); departments and
  positions are global with unique names. Matches CODEX §9 exactly. ✅
- **Correct seed data** — the four real companies with correct Arabic names, six roles,
  global departments/positions, and settings. ✅
- **Backend hygiene** — Form Request validation, soft deletes, indexed columns,
  `latest()` + `paginate()->withQueryString()`, search/status/company filters. ✅
- **Sidebar** — all 15 CODEX modules present; unbuilt ones shown disabled with a
  "not built yet" tooltip. Clean way to preview the full nav without dead links. ✅

---

## 3. Findings (ordered by priority)

### 🔴 High

1. ~~**No login rate limiting / throttling.**~~ ✅ Resolved 2026-07-06 —
   `AuthenticatedSessionController` now throttles at 5 failed attempts per email+IP
   via `RateLimiter`, with a localized lockout message and test coverage.

2. **No authorization layer yet.** Every `FormRequest::authorize()` returns `true`, and
   routes are gated only by `auth`. Six roles are seeded but nothing enforces them.
   Acceptable for Phase 1–2, but RBAC (policies / permission middleware) is a CODEX
   Phase 8 requirement and should be scaffolded before employee/payroll data lands
   (Phase 3+), since salary visibility is permission-gated in the spec.

### 🟡 Medium

3. ~~**Timezone is `Asia/Baghdad`, not `Asia/Riyadh`.**~~ ✅ Resolved 2026-07-06 —
   `.env` `APP_TIMEZONE` and the seeded `system.timezone` are now `Asia/Riyadh`.

4. ~~**No `created_by` / `updated_by` audit fields on org tables.**~~ ✅ Resolved
   2026-07-06 — added a reusable `App\Models\Concerns\TracksBlame` concern (auto-stamps
   the authenticated user on create/update) plus nullable `created_by`/`updated_by`
   FKs on companies, branches, departments, positions, shifts, and employees.

5. ~~**Shifts module missing from Phase 2.**~~ ✅ Resolved 2026-07-06 — global
   `shifts` table + full CRUD (name AR/EN, start/end time, active) added, seeded with
   Morning/Evening/Night, sidebar link, bilingual keys, and test coverage. Note: Shifts
   is not in the fixed 15-item sidebar list of CODEX §23; it was added as a 16th nav
   item since it is a Phase-2 module that must be reachable — relocate under Settings
   later if strict §23 compliance is preferred.

### 🟢 Low / polish

6. **Default admin password** `password` for `admin@smars.local` — fine for local dev,
   but flag it for the production-hardening checklist (CODEX §16, Phase 10).

7. **Dashboard is a Phase-1 shell** — counts companies/users/roles/settings only. Expand
   to the CODEX §21 widgets (Saudi/non-Saudi, Iqama/passport expiry, device status)
   as the underlying data arrives.

8. **`welcome.blade.php`** (default Laravel) is still present and unused — safe to remove.

---

## 4. Phase 3 — what was delivered (2026-07-06)

- **Schema:** `employees` table with all 67 CODEX §11 fields and constraints (global
  uniqueness on `hr_employee_id`/`national_id`/`passport_id`/`employee_code`,
  `decimal(12,2)` money fields, soft deletes, audit authors, filter indexes).
- **Reference data:** `banks` (10 Saudi banks with IBAN codes) and `countries`
  (58 seeded, 18 priority countries ordered per §13) tables + seeders.
- **Validation (`EmployeeRequest`, client + server):** national ID/Iqama prefix rule
  with Saudi/nationality auto-classification, Saudi phone normalization to `+9665…`,
  email hygiene (lowercase/ASCII/unique), passport global uniqueness, IBAN structure +
  ISO-7064 checksum + bank-code match (`App\Rules\SaudiIban`), expiry-not-in-past.
- **CRUD + UX:** list with search/company/branch/department/nationality/status filters,
  sectioned create/edit form (national-ID auto-logic + company→branch filtering JS),
  8-tab employee profile, soft delete + restore route.
- **Tests:** 8 employee tests (14 total, 58 assertions) — all green.

## 5. Remaining follow-ups

1. **RBAC (Finding #2)** is still open and is now the highest-value gap — salary data
   is live and unprotected beyond the `auth` gate. Scaffold policies/permission
   middleware before Phase 7 (Payroll).
2. **Countries list** is a curated 58, not the full ISO set. Extend `CountrySeeder`
   when a complete nationality list is required (§13).
3. **Employee factory / documents:** no `EmployeeFactory` yet; Phase 4 (Employee
   Documents & expiry alerts) is the natural next module.
4. Low-priority polish items #6–8 (default admin password, dashboard widgets, unused
   `welcome.blade.php`) remain.

**Overall:** Phases 1–3 complete, spec-faithful, and covered by a green test suite.
The main open architectural item is role-based authorization.
