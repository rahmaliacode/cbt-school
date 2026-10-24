@echo off
setlocal
title CBT Scheduler - JANGAN DITUTUP
cd /d "%~dp0.."
if not defined CBT_PHP set "CBT_PHP=C:\xampp\php\php.exe"
if not exist "%CBT_PHP%" (echo PHP tidak ditemukan. Set CBT_PHP ke lokasi PHP 8.2+. & pause & exit /b 1)
if not exist vendor\autoload.php (echo Jalankan composer install sebelum hari-H. & pause & exit /b 1)
echo Scheduler CBT: mengumpulkan jawaban otomatis saat waktu sesi habis.
echo Jendela ini boleh di-minimize, JANGAN ditutup.
rem Beri waktu service MySQL menyala saat dijalankan dari folder Startup.
timeout /t 15 /nobreak >nul
"%CBT_PHP%" artisan schedule:work
echo Scheduler berhenti. Klik dua kali start-scheduler.bat untuk menjalankan lagi.
pause
