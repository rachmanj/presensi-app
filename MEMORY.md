**Purpose**: AI's persistent knowledge base for project context and learnings
**Last Updated**: [Auto-updated by AI]

## Memory Maintenance Guidelines

### Structure Standards

- Entry Format: ### [ID] [Title (YYYY-MM-DD)] ✅ STATUS
- Required Fields: Date, Challenge/Decision, Solution, Key Learning
- Length Limit: 3-6 lines per entry (excluding sub-bullets)
- Status Indicators: ✅ COMPLETE, ⚠️ PARTIAL, ❌ BLOCKED

### Content Guidelines

- Focus: Architecture decisions, critical bugs, security fixes, major technical challenges
- Exclude: Routine features, minor bug fixes, documentation updates
- Learning: Each entry must include actionable learning or decision rationale
- Redundancy: Remove duplicate information, consolidate similar issues

### File Management

- Archive Trigger: When file exceeds 500 lines or 6 months old
- Archive Format: `memory-YYYY-MM.md` (e.g., `memory-2025-01.md`)
- New File: Start fresh with current date and carry forward only active decisions

---

## Project Memory Entries

### [M001] Real Sample Data Analysis Findings (2026-07-30) ✅ COMPLETE

**Challenge**: `docs/concept.md` narrative described input/output Excel formats, but implementation needed exact cell-level structure to build a precise parser/exporter.

**Solution**: Inspected all 4 sample files directly (xlrd/openpyxl) — findings written into `docs/action-plan.md` §15. Key discoveries: (1) Format 2 (APS input) stores time as TEXT cells (`"06:03:46"`) and manual codes as NUMBER cells (`1901.0`) — parser must check `cell_type`, not string pattern; (2) HO output template = 48 columns (A–AV), APS = 47 columns (A–AU), genuinely different layouts, not just labels; (3) NIK 10750 (Nurhayani Rusman) appears as row #1 in the APS report despite likely being HO/BO home site — confirms cross-sheet employee assignment is a real, not hypothetical, edge case.

**Key Learning**: Never assume Excel cell "type" from visual format alone — always verify `cell_type`/`data_type` programmatically before writing parser logic. Golden-file regression must account for genuinely different report layouts per site profile, not a single generic template.

### [M002] Action Plan Created for Implementation (2026-07-30) ✅ COMPLETE

**Challenge**: Cursor agents (composer-2.5) implementing Fase 0/1 needed a single, unambiguous spec (migrations, models, routes, services) to avoid inconsistent naming/design decisions across steps.

**Solution**: Created `docs/action-plan.md` covering ERD, 14 migrations with exact columns/indexes, 5 seeders (incl. full 62-cell matrix from `kode-absensi-matrix.md`), model relationships, 6 service class signatures, 3 jobs, frontend tree, complete route list, and phase breakdown.

### [M003] Fase 0 Foundation Implemented (2026-07-30) ✅ COMPLETE

**Challenge**: Greenfield repo had only docs — needed full Laravel 11 + React SPA scaffold with 14-table schema, seed data, HERO client, and admin UI.

**Solution**: Scaffolded Laravel 11 (v11.55) with Sanctum SPA auth, React 18 + Ant Design Pro in `frontend/`, 14 migrations on `presensi_db`, 5 seeders (8 sites, 64 matrix rules, 26 daytype codes, 16 holidays, 2 report templates), `HeroApiClient` with Redis cache + circuit breaker, `SyncHeroMasterData` job, admin CRUD API + ProTable pages.

**Key Learning**: MySQL unique index names >64 chars fail silently during migration — use explicit short names. Vite 8 + Ant Design Pro requires `rc-field-form` as explicit dependency for production build.

### [M004] Fase 1 MVP — Attendance Pipeline (2026-07-30) ✅ COMPLETE

**Challenge**: Implement full fingerprint→engine→review→export pipeline matching Juni 2026 golden files (≥95% cell match).

**Solution**: Built `FingerprintParser` (Format 1 BIFF4 via Python/xlrd fallback + Format 2 PhpSpreadsheet), `AttendanceCodeEngine` pipeline with traces, `ReportExporter` (OpenSpout v5), queue jobs, full API + React pages, `php artisan attendance:golden-test` regression command.

**Key Learning**: HO fingerprint input is BIFF4 (not OLE) — PhpSpreadsheet Xls reader fails; xlrd Python fallback required. Golden test: `reResolveScanNiks` must not null-out `resolved_nik` on golden-seeded scans when no EmployeeMap exists. **Golden test result: HO 99.55%, APS 97.96%, overall 99.04%.**

### [M005] Fase 2 Enhancement — Dashboard, RBAC, Audit (2026-07-31) ✅ COMPLETE

**Challenge**: MVP needed real-time dashboard, overtime hours, PDF export, multi-month comparison, HERO leave balance, role-based access, and audit trail without regressing golden test.

**Solution**: Added `DashboardService` (today stats/trend/overtime), `calculateOvertimeHours` in engine + `overtime_hours` column, `PdfExporter` (dompdf), comparison API + UI, `leave_balance` cache sync, `CheckRole` middleware with 3 seeded users, `audit_logs` table + logging on key actions, fingerprint webhook stub. Golden test still **99.04%**.

### [M006] Production deploy — saphire-two (2026-09-04) ✅ COMPLETE

**LIVE at `http://192.168.32.149:84`** (LAN). Stack: container **php83** (PHP 8.3.30) di `/home/ark-adm/docker-apps/www/php83/presensi`, nginx vhost `:84` (SPA `frontend/dist` + `/api` `/sanctum` fastcgi → php83:9000, same-origin utk Sanctum cookie), DB `presensi_db`/`presensi_acc` di MySQL container, worker `queue-presensi` (queue `sync,default`, timeout 600) + `scheduler-presensi` (schedule:run 60s; SyncHeroMasterData **twiceDaily(2,14)**). **TANPA Redis** — `CACHE_STORE=database`. HERO prod `http://192.168.32.146:8080` (key reuse ESD). Timezone Asia/Makassar.
**Pitfalls nyata**: (1) composer.lock di-resolve di PHP 8.5 (symfony 8.1/openspout ≥5.6 butuh ≥8.4) → pin `config.platform.php=8.3.30` + `openspout ^5.3` di composer.json (commit 548dcf4); (2) HTTP apt deb.debian.org diblokir jaringan ARKA (NOSPLIT) → Dockerfile php83 sed URIs ke https; (3) php83 image butuh composer (`COPY --from=composer:2`) + `python3-xlrd` (FingerprintParser BIFF4 shell_exec; jangan andalkan path venv dev yg hardcode); (4) **HERO payload NESTED** (`employee:{fullname}`, `position:{...department:{department_name}}`, `project:{project_code}`) — SyncHeroMasterData sempat asumsi flat → crash "Array to string conversion"; normalizeEmployee + fallback flat (commit 524ac14); (5) `/api/leave/employees/{id}/balance` = `{}` kosong di HERO & N+1 HTTP bikin timeout → leave_balance di-skip di sync (null); (6) worker wajib `--queue=sync,default` (job onQueue('sync')); (7) sync penuh = rewrite 9.777 baris raw JSON ≈ 11-13 menit → timeout job & worker 1200; (8) chown storage via `docker exec -u root php83 chown -R 33:33` (ark-adm non-root tak bisa chown); (9) curl login butuh `Origin: http://192.168.32.149:84` (Sanctum fromFrontend cek Origin/Referer); (10) `LOG_LEVEL=warning` → INFO "HERO sync completed" tak muncul di laravel.log (jangan salah diagnosa); (11) **DashboardService todaySummary crash beruntun di data nyata**: closure tanpa `use ($presentNiks)` lalu `use ($today)` → 500; dan dashboard sempat fetch activity HERO per karyawan (999 HTTP → rate-limit + circuit spam) → getEmployeeActivity kini **cache-only** (baca `raw['activity']`, [] jika kosong; commit 5219f56 + 03239e8 + test DashboardServiceTest). (12) **Mismatch keys activity (SELESAI 7c97416)**: kode lama baca `leaves`/`lots` tapi HERO asli kirim `leave_requests`/`official_travels`/`administrations`/`summary` → `HeroActivityNormalizer` baru: leave filter status approved/closed, skip cancellations & `is_lsl_cashout_only`, mapping nama/kategori cuti→1901/1902/1903/1904/1905 (annual/lsl/cuti→1901, sakit/izin by paid, default 1901); LOT dari `official_travel_date` UTC→Asia/Makassar + durasi, site di-parse dari destination (longest-match; "001H - BO - Jakarta"→BO); engine & dashboard pakai hasil normalize (cache `raw.activity` di-write-back engine). Live-verified: nik 13100 cuti 24/8→1901; nik 10656 LOT 6/8→BO. `overtime_requests` HERO bentuk belum dikenal → overtimes passthrough, upgrade OT dari HERO dormant. 9.795 item /api/employees → 9.777 cache (18 missing/dup nik di-skip by design).
