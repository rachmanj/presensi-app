# DESIGN.md - ARKA Presensi

Arah visual disampaikan Iwan (2026-09-04): **segar dan tidak monoton, enak di mata**.
Dibaca sebagai: aplikasi internal HR/payroll untuk staf HR ARKA, gaya clean-fresh, dial ENERGY 2 / RHYTHM 3 / MOTION 1.

Status: dokumen arah. Token tema belum diterapkan; penerapan dilakukan sebagai pass terpisah setelah palet disetujui.

## Identitas

- Nama: ARKA Presensi. App absensi internal: data padat (matriks 30+ hari, kode absensi), dipakai tiap hari oleh HR.
- Satu kalimat karakter: tenang, rapi, tapi tidak kaku; hierarki jelas sehingga mata langsung jatuh ke hal yang perlu tindakan HR (sel kosong, kode perlu review, angka absen).

## Palet (usulan, menunggu persetujuan)

- Primary: teal `#0d9488` (segar, satu keluarga dengan suite akuntansi ARKA lain; bukan biru default AntD #1677ff).
- Netral: slate/grey AntD token (`colorBgLayout #f5f7fa`, teks #1f2937 level).
- Accent: amber `#d97706` khusus sinyal "perlu perhatian" (belum di-review, akan di-generate).
- Status semantik tetap token AntD (success/hijau, error/merah) karena bermakna; tidak dihitung sebagai warna aksen.
- Batas aktif: 2 warna inti (teal + slate) + 1 aksen amber. (R-29)

## Tipografi

- Inter atau system-ui stack: terbaca di tabel padat, netral. Alasan: angka & kode absensi harus diskriminatif di ukuran kecil; bukan font dekoratif.
- Tidak ada monospace besar, tidak ada label uppercase ber-letterspacing. (R-06)

## Layout & ritme (RHYTHM 3)

- Tabel adalah warga utama (review grid, import, mapping); kartu statistik hanya pelengkap ringkas.
- Varian komposisi disengaja: daftar periode ringkas, grid review penuh lebar, halaman Mapping dua panel (tabel + form). Tidak semua halaman memakai template yang sama.
- Satu fokus per layar: di Review, fokusnya sel yang belum di-review; di Dashboard, metrik yang butuh tindakan (Absen diberi aksen).

## Motion (MOTION 1)

- Hover/focus state saja; auto-poll memakai spinner halus, tanpa animasi dekoratif. (R-19)

## Keputusan & alasan (R-31, satu baris per keputusan)

- Teal primary: segar dan konsisten dengan ekosistem ARKA, bukan biru AI default.
- Dark mode default light: tool kantor dipakai siang hari; toggle tetap tersedia.
- Empty/error state eksplisit: HR tidak boleh salah baca nol dari kegagalan jaringan sebagai kehadiran nol.
- Bahasa UI Indonesia: pengguna HR ARKA berbahasa Indonesia; kode/status data tetap apa adanya.
