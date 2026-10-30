@echo off
setlocal
cd /d "%~dp0.."
rem Cari PHP: CBT_PHP, lalu XAMPP, lalu Laragon (versi PHP 8 terbaru), lalu php di PATH.
if not defined CBT_PHP if exist "C:\xampp\php\php.exe" set "CBT_PHP=C:\xampp\php\php.exe"
if not defined CBT_PHP for /d %%D in ("C:\laragon\bin\php\php-8*") do set "CBT_PHP=%%D\php.exe"
if not defined CBT_PHP for /f "delims=" %%P in ('where php 2^>nul') do if not defined CBT_PHP set "CBT_PHP=%%P"
if not defined CBT_PHP (echo PHP tidak ditemukan. Set CBT_PHP ke lokasi PHP 8.2+. & pause & exit /b 1)
if not exist "%CBT_PHP%" (echo PHP tidak ditemukan. Set CBT_PHP ke lokasi PHP 8.2+. & pause & exit /b 1)
if not exist vendor\autoload.php (echo Jalankan composer install sebelum hari-H. & pause & exit /b 1)
start "CBT auto-submit scheduler" "%CBT_PHP%" artisan schedule:work
"%CBT_PHP%" artisan serve --host=0.0.0.0 --port=8000
pause
