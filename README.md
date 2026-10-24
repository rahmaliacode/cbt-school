# CBT School — lomba siswa melalui LAN

Aplikasi CBT offline untuk satu PC server dan browser peserta. Laravel 12, MySQL/InnoDB, Blade, dan aset lokal tanpa CDN atau build Node. Nama tabel, kolom, model, dan identifier kode menggunakan bahasa Inggris. Tampilan berbahasa Indonesia mengikuti susunan mockup yang diberikan.

## Persyaratan server

- **PHP 8.2 atau lebih baru** (Laravel 12). Aktifkan `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`.
- MySQL 8+ atau MariaDB 10.4+ dengan tabel InnoDB. XAMPP menyediakan MariaDB sebagai pengganti kompatibel MySQL.
- Composer 2 dan Git dibutuhkan pada persiapan/pembaruan, bukan browser peserta.
- PC server dan peserta berada pada jaringan yang sama. Tidak perlu internet saat ujian.

Gunakan **XAMPP portable PHP 8.2**, misalnya XAMPP 8.2.12 untuk Windows. Laravel 12 kompatibel dengan PHP 8.2; dependency dikunci dengan platform PHP 8.2.12 supaya pembaruan Composer tidak memilih paket yang memerlukan PHP 8.3. XAMPP 8.0/8.1 tidak kompatibel. Set `CBT_PHP` pada launcher jika XAMPP berada di folder lain. Verifikasi dengan `php -v` dan `php -m` sebelum instalasi.

Referensi: [Laravel 12 requirements](https://laravel.com/docs/12.x/deployment), [XAMPP downloads](https://www.apachefriends.org/download.html).

## Instalasi pertama (saat masih tersedia internet)

```bash
git clone https://github.com/rahmaliacode/cbt-school.git
cd cbt-school
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.example .env
```

Windows Command Prompt: gunakan `copy .env.example .env`. Masukkan direktori PHP yang kompatibel ke PATH, atau jalankan setiap perintah PHP dengan path lengkap.

1. Jalankan database dari XAMPP Control Panel. Buat database `cbt_school` dengan kolasi `utf8mb4_unicode_ci` dan user khusus yang memiliki hak atas database tersebut. Isi `DB_USERNAME` dan `DB_PASSWORD` di `.env`.
2. Isi `APP_URL` dengan alamat IP server, misalnya `http://192.168.1.10:8000`. Pertahankan `APP_DEBUG=false`. IP server harus tetap selama lomba (static IP atau DHCP reservation).
3. Jalankan:

```bash
php artisan key:generate
php artisan migrate --force
php artisan cbt:create-admin panitia@example.com
php artisan config:cache
php artisan view:cache
```

Password admin dimasukkan secara tersembunyi (minimal 12 karakter), tidak ada akun/password default. Peserta dibuat oleh panitia; tidak ada registrasi publik.

## Menjalankan di lokasi

- Jalankan database XAMPP. Pastikan proses server **dan scheduler** tetap berjalan.
- Windows: klik `scripts/start-server.bat`. Jika lokasi PHP berbeda, set dahulu `CBT_PHP` ke file `php.exe` yang kompatibel.
- Windows dengan Apache XAMPP: jadikan Apache dan MySQL service (centang **Svc** di XAMPP Control Panel sebagai Administrator), lalu taruh shortcut `scripts/start-scheduler.bat` di folder `shell:startup` agar scheduler terbuka sendiri setiap login Windows.
- Linux/macOS: `bash scripts/start-server.sh`.
- Alternatif manual, buka dua terminal:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan schedule:work
```

Browser peserta membuka `http://IP-SERVER:8000`; panitia membuka `/admin/login`. Izinkan koneksi port 8000 pada firewall jaringan privat dan matikan WiFi client isolation. Browser peserta tidak perlu instalasi, Node, Composer, akun cloud, atau pengaturan aplikasi.

Scheduler memfinalisasi sesi habis waktu walaupun semua browser tertutup. Request siswa/panitia juga memeriksa expiry sebagai fallback. Jangan mematikan scheduler saat lomba. Waktu server menentukan deadline, bukan jam peserta. Durasi adalah **batas seluruh sesi**; peserta yang mulai terlambat memperoleh sisa waktu sesi.

`artisan serve` cocok untuk simulasi dan lomba kecil. Untuk banyak peserta, gunakan Apache XAMPP dengan document root **`public/`**, `mod_rewrite`, dan `AllowOverride All`; tetap jalankan scheduler. Atur virtual host/port agar URL peserta langsung mengarah ke `public/`, jangan meng-expose root proyek. Uji kapasitas pada perangkat/jaringan nyata sebelum menentukan jumlah peserta. Perangkat server harus tidak sleep selama sesi.

## Alur panitia

1. Buat master mata pelajaran dan peserta.
2. Download template `.xlsx` melalui **Paket soal**. Header mengikuti spesifikasi `No | Mapel | Soal | Opsi A | Opsi B | Opsi C | Opsi D | Opsi E | Kunci`; nama database tetap Inggris. `Mapel` harus cocok dengan master. Kunci A–D/E, Opsi E kosong berarti empat opsi.
3. Import Excel/XLS/CSV atau tempel baris TAB dari Excel. Maksimal file 10 MB/5000 soal. Validasi menampilkan nomor baris dan alasan gagal. Jika satu baris gagal, **seluruh import tidak disimpan**. Preview valid harus dikonfirmasi sebelum transaksi menyimpan data. Duplikat nomor per mapel ditolak.
4. Soal dapat ditambah, diubah, dan dihapus manual sebelum dipakai. Soal yang sudah masuk snapshot sesi tidak dapat diubah/hapus; buat soal baru agar riwayat tetap utuh.
5. Buat sesi, pilih peserta, jumlah soal, durasi, skor, 4/5 opsi, tie-breaker, dan pengacakan. Paket memakai **N soal pertama berdasarkan nomor** dari mapel. Jumlah opsi semua soal paket harus cocok. Token unik 6–32 karakter dibagikan kepada peserta.
6. Mulai sesi dengan konfirmasi. Sistem mengunci konfigurasi dan snapshot teks/opsi/kunci. Peserta menunggu hingga sesi dimulai lalu memilih mulai.
7. Monitor hanya menampilkan status, progres, waktu, dan heartbeat koneksi; tidak ada jawaban/kunci/skor. Polling 5 detik, heartbeat siswa 10 detik. Status koneksi adalah perkiraan berdasarkan request terakhir, bukan pemeriksaan perangkat.
8. Detail hasil tersedia untuk attempt yang sudah submit. Leaderboard selama sesi masih berjalan adalah sementara dan hanya berisi attempt selesai. Export CSV dapat dibuka di Excel dan memakai data/peringkat yang sama. Tidak ada export PDF.
9. Reset resmi tersedia di monitor untuk sesi yang masih berjalan. Memerlukan alasan dan konfirmasi; menghapus attempt beserta jawaban/hasil, menyimpan salinan di audit log, lalu memberi peserta kesempatan mulai dengan sisa waktu sesi.

Pengaturan `show_live_score` mengatur **leaderboard panitia**: on menampilkan peserta submit; off menahan nilai/detail/ekspor sampai sesi berakhir. Monitor selalu menyembunyikan jawaban dan skor. Peserta melihat skor totalnya sendiri di halaman selesai segera setelah submit; peringkat/leaderboard tetap hanya untuk panitia. Buka kembali hasil wajib alasan tercatat.

## Keamanan jawaban dan penilaian

- Unique constraint `(exam_session_id, participant_id)` memastikan satu attempt. Unique jawaban per attempt/soal dan unique result per attempt.
- Operasi memakai transaksi dan kunci baris dengan urutan sesi → attempt; submit berulang mengembalikan hasil yang sudah ada. Request jawaban setelah deadline ditolak/difinalisasi server.
- Urutan soal/opsi diacak sekali lalu disimpan. Penilaian menggunakan ID opsi asli, bukan huruf tampilan. Snapshot mencegah perubahan bank soal mengubah hasil.
- Jawaban pilihan auto-save. Revisi lama tidak menimpa revisi terbaru. Gunakan satu tab browser per peserta; bila tab ganda terdeteksi lewat konflik revisi, sinkronkan ulang.
- Jika LAN terputus, jawaban yang sudah tersimpan tetap ada. Perubahan yang belum terkonfirmasi antre di browser dan dicoba ulang saat LAN kembali. Hanya jawaban yang diterima server **sebelum deadline** dihitung. Jangan bergantung pada antrean lokal sebagai bukti jawaban telah tersimpan; periksa indikator.
- Timer browser dihitung dari waktu server dengan jam monotonik browser dan disinkronkan tiap 10 detik. Server tetap berwenang walaupun JavaScript atau jam peserta dimanipulasi.
- Peringkat memakai skor terbesar, lalu jumlah benar atau durasi pengerjaan resmi sesuai konfigurasi. Nilai tie-breaker sama menghasilkan peringkat kompetisi yang sama (1, 1, 3).
- Login admin memakai session auth + middleware admin; siswa memakai identitas peserta dalam session server. Semua mutasi memakai CSRF dan validasi server. Endpoint siswa menyaring kunci/skor. Token siswa dibatasi per sesi; nomor peserta/token adalah mekanisme akses sesuai spesifikasi, bukan password pribadi.
- `activity_logs` mencatat mulai/akhir sesi, mulai attempt, submit/auto-submit, import, dan reset. Akses DB/server hanya untuk panitia tepercaya.

## Pembaruan dan backup

Saat tidak ada sesi berjalan, backup database dan `.env` ke lokasi aman di luar Git. Gunakan `mysqldump` (atau export phpMyAdmin); lakukan uji restore sebelum hari-H. **Jangan menjalankan `migrate:fresh` pada data lomba.**

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Restart server dan scheduler setelah pembaruan. Composer membutuhkan internet saat instalasi/update; seluruh dependency di `vendor/` harus sudah tersedia di PC server sebelum offline. Tidak ada frontend build. `.env`, vendor, cache, log, dump database, dan data peserta tidak di-commit.

## Verifikasi

```bash
composer install
php artisan test
php artisan view:cache
node --check public/assets/exam.js
node --check public/assets/monitor.js
```

Tests mencakup expiry, opsi asing, snapshot, randomisasi konsisten, satu attempt, double-submit, penilaian, tie-breaker, otorisasi, privasi monitor/state, validasi dan rollback import. Default tests memakai SQLite untuk kemudahan lokal; workflow GitHub memakai MySQL 8.4 untuk memeriksa kompatibilitas database. SQLite tidak membuktikan perilaku row-lock MySQL pada request bersamaan.

Sebelum lomba: simulasi beberapa peserta pada LAN tanpa internet, refresh di tengah ujian, putus/sambung WiFi, submit berulang, habis waktu dengan browser tertutup, periksa hasil/export, uji reset, dan backup/restore. Uji beban nyata diperlukan untuk memastikan kapasitas PC, Apache, dan router.

## Struktur data

`users`, `subjects`, `questions`, `answer_options`, `participants`, `exam_sessions`, `exam_session_participant`, `attempts`, `participant_answers`, `results`, `activity_logs`. Waktu disimpan UTC; tampilan waktu acara WIB. Skor menggunakan DECIMAL dua digit; konfigurasi tersimpan pada `configuration_snapshot`, paket soal pada `question_snapshot`.

## Import peserta dan audit plan

Import siswa: **Peserta → Template Excel peserta → Preview import → Simpan**. Header `Nomor Peserta | Nama | Asal Sekolah`; nomor harus unik. Excel/CSV/paste didukung. Preview tidak menulis database.

Lihat [PLAN_REVIEW.md](PLAN_REVIEW.md) untuk alur dan gap terhadap PDF. Export XLSX memiliki sheet Ranking/Configuration/Audit; PDF melalui cetak browser/Save as PDF. Setelah update jalankan `php artisan migrate` untuk kolom kunci hasil.

### Gambar soal dan juara per mapel

Bank Soal mendukung satu gambar JPG/PNG/WebP per soal, maksimal 2 MB (6000 × 6000 piksel). Import Excel tetap memakai template teks; lampirkan gambar melalui edit soal setelah import, sebelum sesi dimulai. Peserta dapat memperbesar gambar. Gambar disimpan privat di `storage/app/private/question-images`, diberikan hanya melalui akses admin atau peserta dengan attempt aktif, dan disalin sebagai referensi tetap ke snapshot sesi. Sertakan folder gambar saat backup bersama database; tidak perlu `storage:link`. Jalankan `php artisan migrate` setelah memperbarui aplikasi.

Menu Juara per Mapel dan dashboard menampilkan peringkat 1 dari satu sesi FINAL per mapel, dengan nama peserta, sekolah, poin, sesi dan aturan tie-break. Default adalah sesi terakhir dikunci; panitia bisa memilih sesi FINAL lain. Semua peserta seri ditampilkan bersama. Hasil DRAFT tidak masuk ringkasan.

### Mode fokus ujian

Peserta mengaktifkan layar penuh melalui tombol sebelum membuka soal. Jika layar penuh tidak didukung/ditolak browser, peserta dapat melanjutkan dengan pengawasan panitia dan kondisi ini dicatat. Selama attempt aktif, tautan keluar dan navigasi Back biasa dibatasi, shortcut reload diperingatkan, serta browser diminta menampilkan konfirmasi tutup/reload. Setelah submit terkonfirmasi atau server menyatakan selesai, pembatasan dilepas. Logout aktif diarahkan kembali ke ujian.

Perpindahan tab, kehilangan fokus jendela, keluar layar penuh dan upaya navigasi dicatat melalui endpoint beridentitas peserta, idempotent per UUID, maksimal 500 catatan per attempt. Antrean maksimal 100 catatan lokal mencoba kembali saat LAN tersedia. Monitor menampilkan jumlah sinyal untuk ditinjau; tidak ada pengurangan skor atau diskualifikasi otomatis. Catatan dari JavaScript dapat hilang/dimanipulasi dan bukan bukti kecurangan.

Website biasa tidak dapat melarang Alt+Tab, menutup aplikasi, membuka perangkat lain, atau menjamin dialog keluar pada semua browser/mobile. Untuk penguncian perangkat, siapkan kiosk/MDM atau Safe Exam Browser di luar aplikasi; integrasi SEB belum disediakan. Uji manual browser dan komputer lab sebelum lomba.
