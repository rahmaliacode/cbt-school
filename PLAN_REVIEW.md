# Audit rencana CBT — 11 Oktober 2026

Sumber: CBT Application Plan, PDF 12 halaman dari pengguna. Audit ini tidak menyatakan seluruh roadmap sudah selesai. Demo memakai browser lokal; aplikasi lomba memakai Laravel + MySQL di LAN.

| Kebutuhan PDF | Laravel | Demo online |
| --- | --- | --- |
| Import siswa | XLSX/XLS/CSV/paste; template, preview, error per baris, konfirmasi, transaksi atomik, audit | XLSX/CSV/paste; validasi dan simpan lokal |
| Import soal | Excel/CSV/paste; validasi No, Mapel, teks, opsi, kunci, duplikat; preview dan transaksi | XLSX/CSV/paste dengan preview dan validasi |
| Skor benar/salah/kosong | Konfigurasi per sesi, snapshot saat mulai | Konfigurasi dan snapshot lokal |
| Jumlah soal/durasi/4–5 opsi | Tersedia dan divalidasi terhadap bank soal | Tersedia dan divalidasi |
| Tie-break tiga mode | Nonaktif, benar terbanyak, durasi tercepat; peringkat tetap seri jika sama | Sama |
| Pengacakan stabil | Disimpan sekali per attempt; kunci pakai ID opsi | Disimpan sekali per attempt lokal |
| Start serentak & ruang tunggu | Deadline sama dari server | Simulasi deadline sama dari browser |
| Autosave & submit idempoten | Antrean localStorage, revision, retry API; reject setelah deadline | Simulasi lokal, bukan sinkron server |
| Monitoring tanpa jawaban/nilai | Polling 5 detik; progres, waktu server, last seen | Progres browser; tidak menggabungkan perangkat |
| Skor live on/off | On: leaderboard peserta submit; off: nilai/detail/export ditahan sampai sesi berakhir | Sama untuk data lokal |
| Kunci hasil DRAFT/FINAL | Setelah sesi berakhir, kunci idempoten; buka kembali wajib alasan/audit | Simulasi kunci dan audit |
| Hasil peserta | Setelah panitia mengunci hasil | Sama |
| Ekspor Excel | Ranking, Configuration (bobot/status/operator/waktu), Audit | Ranking, Configuration, Audit lokal |
| PDF | Cetak browser / Save as PDF; belum laporan khusus | Cetak browser / Save as PDF |

## Alur saat ini

Master mapel → import siswa → import soal → sesi dan penilaian → ruang tunggu → panitia mulai (snapshot dan deadline) → pengerjaan/autosave/submit → monitoring → panitia akhiri → verifikasi rekap/detail → kunci FINAL → ekspor Excel atau cetak PDF.

## Gap terhadap keseluruhan PDF

- Hak akses pengawas/operator hasil belum dipisah; sekarang admin/non-admin dan peserta. Navigasi demo bebas, bukan autentikasi produksi.
- Belum master event/template/default mapel dan paket soal berversi; sekarang sesi langsung dan N soal pertama per mapel.
- Belum cache halaman/soal untuk reload tanpa LAN, IndexedDB, batch acknowledgment dan tiga indikator sinkron lengkap. Antrean jawaban localStorage sudah ada.
- Belum grace pasca-deadline yang diaudit; jawaban terlambat ditolak.
- Belum device binding/otorisasi pindah perangkat; reset attempt beralasan sudah ada.
- Belum jadwal bentrok/perpanjangan resmi, cetak kartu login, berita acara dan laporan individual PDF khusus.
- Backup/restore masih prosedur manual di README, belum dashboard/otomasi atau uji restore operasional.
- Belum uji beban/perangkat hari-H, gangguan LAN/listrik, restart server, dan konkurensi MySQL skala target.

## Validasi

29 test / 189 assertion lokal (PHP 8.3, SQLite in-memory), Blade view cache dan JS syntax. CI repository memakai PHP 8.2 dan MySQL 8.4. Smoke demo meliputi halaman, impor, ranking, skor, privacy hasil dan XLSX round-trip. Browser visual QA tidak tersedia di lingkungan ini; perlu UAT pengguna. SQLite tidak membuktikan locking MySQL atau kesiapan lomba.

- Gambar soal JPG/PNG/WebP: tersedia melalui unggah manual/edit setelah import, penyimpanan privat, snapshot dan pembesaran di ujian. Gambar pada opsi jawaban/import gambar tertanam Excel belum tersedia.
- Ringkasan juara per mapel: tersedia dari sesi FINAL terpilih, semua juara seri ditampilkan.
