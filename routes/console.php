<?php

use App\Models\User;
use App\Services\ExamEngine;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

Artisan::command('cbt:create-admin {email}', function () {
    $password = $this->secret('Password admin (minimal 12 karakter)');
    if (strlen($password) < 12) {
        $this->error('Password terlalu pendek.');

        return 1;
    }
    if (User::where('email', $this->argument('email'))->exists()) {
        $this->error('Email sudah digunakan.');

        return 1;
    }User::forceCreate(['name' => 'Panitia', 'email' => $this->argument('email'), 'password' => Hash::make($password), 'is_admin' => true]);
    $this->info('Admin berhasil dibuat.');
});
Artisan::command('cbt:expire', function () {
    app(ExamEngine::class)->expire();
    $this->info('Expired sessions finalized.');
});
Schedule::command('cbt:expire')->everySecond()->withoutOverlapping();
