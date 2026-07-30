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

**Key Learning**: Several concept decisions remain genuinely open (day6/day7 definition for Coal sites, HARI KERJA formula, cross-sheet employee assignment rule) — these are flagged explicitly in `docs/action-plan.md` §14 rather than guessed, so implementation can stub them safely instead of producing silently wrong output.
