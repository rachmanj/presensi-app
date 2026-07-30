Keep your task management simple and focused on what you're actually working on:

```markdown
**Purpose**: Track current work and immediate priorities
**Last Updated**: [Auto-updated by AI]

## Task Management Guidelines

### Entry Format

Each task entry must follow this format:
[status] priority: task description [context] (completed: YYYY-MM-DD)

### Context Information

Include relevant context in brackets to help with future AI-assisted coding:

- **Files**: `[src/components/Search.tsx:45]` - specific file and line numbers
- **Functions**: `[handleSearch(), validateInput()]` - relevant function names
- **APIs**: `[/api/jobs/search, POST /api/profile]` - API endpoints
- **Database**: `[job_results table, profiles.skills column]` - tables/columns
- **Error Messages**: `["Unexpected token '<'", "404 Page Not Found"]` - exact errors
- **Dependencies**: `[blocked by auth system, needs API key]` - blockers

### Status Options

- `[ ]` - pending/not started
- `[WIP]` - work in progress
- `[blocked]` - blocked by dependency
- `[testing]` - testing in progress
- `[done]` - completed (add completion date)

### Priority Levels

- `P0` - Critical (app won't work without this)
- `P1` - Important (significantly impacts user experience)
- `P2` - Nice to have (improvements and polish)
- `P3` - Future (ideas for later)

--- Example

# Current Tasks

## Working On Now

- `[WIP] P1: Implement user authentication [src/auth/login.tsx, Firebase Auth]`

## Up Next (This Week)

- `[ ] P0: Fix database connection timeout [src/db/connection.ts, line 23]`
- `[ ] P1: Add error handling to API calls [API endpoints: /users, /profile]`

## Blocked/Waiting

- `[blocked] P2: Add payment integration [waiting for Stripe API keys]`

## Recently Completed

- `[done] P0: Set up database schema [users table, profiles table] (completed: 2025-01-15)`
- `[done] P1: Create basic routing [React Router setup] (completed: 2025-01-14)`

## Recently Completed

- `[done] P0: Create detailed phase-by-phase implementation action plan [docs/action-plan.md] (completed: 2026-07-30)`

## Up Next

- `[ ] P0: Fase 0 Step 0.1 — Laravel 11 + Sanctum scaffold, React/Vite frontend scaffold [docs/action-plan.md §11]`
- `[ ] P0: Fase 0 Step 0.2 — create presensi_db, run 14 migrations [docs/action-plan.md §5]`
- `[ ] P0: Fase 0 Step 0.3 — run 5 seeders (Site, MatrixRule, SiteDaytypeCode, HolidayCalendar, ReportTemplate) [docs/action-plan.md §6]`
- `[blocked] P1: HolidayCalendarSeeder needs confirmed 2026 national holiday dates from Iwan [docs/action-plan.md §14.6]`

## Quick Notes

- `docs/action-plan.md` adalah single source of truth untuk implementasi Cursor agent (composer-2.5) — semua migration/model/route/service sudah didefinisikan lengkap, tidak perlu menebak.
- Beberapa keputusan masih **open** (lihat `docs/action-plan.md` §14) dan harus dikonfirmasi Iwan sebelum modul terkait (Coal Project day6/day7, role/permission, employee-to-sheet assignment) diimplementasikan penuh.
```
