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

- `[ ] P0: Fase 1 Step 1.1 — FingerprintParser + Import module [docs/action-plan.md §11]`

## Up Next (This Week)

- `[ ] P0: Fase 1 — MVP attendance engine, review grid, export [docs/action-plan.md §11]`

## Blocked/Waiting

- `[blocked] P1: HolidayCalendarSeeder needs confirmed 2026 national holiday dates from Iwan [docs/action-plan.md §14.6]`
- `[blocked] P1: HERO API connectivity — needs HERO_API_KEY and verified HERO_BASE_URL [docs/action-plan.md §14.2]`

## Recently Completed

- `[done] P0: Fase 0 — Laravel 11 + Sanctum + React/AntD frontend scaffold (completed: 2026-07-30)`
- `[done] P0: Fase 0 — 14 migrations + 5 seeders on presensi_db (completed: 2026-07-30)`
- `[done] P0: Fase 0 — HeroApiClient + SyncHeroMasterData job + hero:sync-test command (completed: 2026-07-30)`
- `[done] P0: Fase 0 — Auth SPA + AppLayout + Admin CRUD pages (completed: 2026-07-30)`
- `[done] P0: Create detailed phase-by-phase implementation action plan [docs/action-plan.md] (completed: 2026-07-30)`

## Quick Notes

- `docs/action-plan.md` adalah single source of truth untuk implementasi Cursor agent (composer-2.5) — semua migration/model/route/service sudah didefinisikan lengkap, tidak perlu menebak.
- Beberapa keputusan masih **open** (lihat `docs/action-plan.md` §14) dan harus dikonfirmasi Iwan sebelum modul terkait (Coal Project day6/day7, role/permission, employee-to-sheet assignment) diimplementasikan penuh.
```
