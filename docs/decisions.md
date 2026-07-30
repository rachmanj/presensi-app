**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: [Auto-updated by AI]

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
   - ✅ Pros: [Benefits]
   - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
   - ✅ Pros: [Benefits]
   - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

### Decision: Detailed Implementation Action Plan as Single Source of Truth - 2026-07-30

**Context**: `docs/concept.md` (disetujui Iwan) mendefinisikan arsitektur, ERD, dan modul di level konsep, tapi belum cukup presisi (nama tabel fisik, kolom migrasi exact, daftar route, spesifikasi service) untuk dieksekusi agent Cursor (composer-2.5) tanpa menebak keputusan desain.

**Options Considered**:

1. **Biarkan agent menebak detail saat implementasi berjalan**
   - ✅ Pros: lebih cepat mulai coding
   - ❌ Cons: risiko inkonsistensi nama tabel/route/model antar step, sulit di-review, golden-file regression jadi tidak reproducible
2. **Buat dokumen action plan terpisah yang detail (migration spec, model spec, route list, service signature)**
   - ✅ Pros: agent implementasi tidak perlu menebak; mudah di-review sebelum eksekusi; jadi kontrak yang bisa diverifikasi
   - ❌ Cons: effort awal lebih besar; perlu di-maintain bila konsep berubah

**Decision**: Membuat `docs/action-plan.md` — dokumen implementasi lengkap berisi ERD fisik, 14 migration spec (kolom+index+FK), 5 seeder plan, model list, 6 service class signature, 3 job spec, frontend component tree, route list lengkap per modul, breakdown Fase 0/1/2, testing plan, dan daftar open items yang butuh keputusan Iwan.

**Rationale**: Berdasarkan analisis langsung file sample Juni 2026 (`/tmp/email_attachments/`), ditemukan detail kritis yang tidak eksplisit di `concept.md` (mis. tipe sel Format 2 waktu=string vs kode=number, layout kolom HO 48 vs APS 47 berbeda struktur, kasus karyawan lintas-sheet Nurhayani Rusman NIK 10750) — detail ini harus didokumentasikan sebelum implementasi agar parser & exporter presisi sejak awal, bukan trial-error saat golden-file test gagal.

**Implementation**: `docs/action-plan.md` dibuat sebagai dokumen tunggal 1200+ baris; setiap Fase/Step di §11 mereferensi bagian spesifik dokumen (migration spec §5, model spec §7, dst.) agar agent implementasi tidak perlu mencari konteks di tempat lain.

**Review Date**: Setelah Fase 0 selesai — evaluasi apakah spesifikasi cukup presisi atau perlu direvisi berdasarkan pengalaman implementasi nyata.

---
