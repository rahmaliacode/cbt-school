@echo off
setlocal
cd /d "%~dp0.."
if not defined CBT_PHP set "CBT_PHP=C:\xampp\php\php.exe"
if not exist "%CBT_PHP%" (echo PHP tidak ditemukan. Set CBT_PHP ke lokasi PHP 8.2+. & pause & exit /b 1)
if not exist vendor\autoload.php (echo Jalankan composer install sebelum hari-H. & pause & exit /b 1)
start "CBT auto-submit scheduler" "%CBT_PHP%" artisan schedule:work
"%CBT_PHP%" artisan serve --host=0.0.0.0 --port=8000
pause
