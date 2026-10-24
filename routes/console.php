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
Artisan::command('cbt:reset-admin-password {email}', function () {
    $user = User::where('email', $this->argument('email'))->where('is_admin', true)->first();
    if (! $user) {
        $this->error('Admin dengan email tersebut tidak ditemukan. Admin terdaftar: '.(User::where('is_admin', true)->pluck('email')->implode(', ') ?: '-'));

        return 1;
    }
    $password = $this->secret('Password baru (minimal 12 karakter)');
    if (strlen((string) $password) < 12) {
        $this->error('Password terlalu pendek.');

        return 1;
    }
    if ($this->secret('Ulangi password baru') !== $password) {
        $this->error('Password tidak sama.');

        return 1;
    }
    $user->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
    app(ExamEngine::class)->log('admin_password_reset', ['user_id' => $user->id, 'email' => $user->email]);
    $this->info('Password admin diperbarui.');
});
Artisan::command('cbt:expire', function () {
    app(ExamEngine::class)->expire();
    $this->info('Expired sessions finalized.');
});
Schedule::command('cbt:expire')->everySecond()->withoutOverlapping();
