@echo off
setlocal
title CBT Scheduler - JANGAN DITUTUP
cd /d "%~dp0.."
rem Cari PHP: CBT_PHP, lalu XAMPP, lalu Laragon (versi PHP 8 terbaru), lalu php di PATH.
if not defined CBT_PHP if exist "C:\xampp\php\php.exe" set "CBT_PHP=C:\xampp\php\php.exe"
if not defined CBT_PHP for /d %%D in ("C:\laragon\bin\php\php-8*") do set "CBT_PHP=%%D\php.exe"
if not defined CBT_PHP for /f "delims=" %%P in ('where php 2^>nul') do if not defined CBT_PHP set "CBT_PHP=%%P"
if not defined CBT_PHP (echo PHP tidak ditemukan. Set CBT_PHP ke lokasi php.exe 8.2+. & pause & exit /b 1)
if not exist "%CBT_PHP%" (echo PHP tidak ditemukan di %CBT_PHP%. Set CBT_PHP ke lokasi php.exe 8.2+. & pause & exit /b 1)
if not exist vendor\autoload.php (echo Jalankan composer install sebelum hari-H. & pause & exit /b 1)
echo Scheduler CBT: mengumpulkan jawaban otomatis saat waktu sesi habis.
echo PHP: %CBT_PHP%
echo Jendela ini boleh di-minimize, JANGAN ditutup.
rem Beri waktu MySQL menyala saat dijalankan dari folder Startup.
timeout /t 15 /nobreak >nul
"%CBT_PHP%" artisan schedule:work
echo Scheduler berhenti. Klik dua kali start-scheduler.bat untuk menjalankan lagi.
pause
