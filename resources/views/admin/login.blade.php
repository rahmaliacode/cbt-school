@extends('layout') @section('title','Login Panitia · CBT Lomba') @section('content')<div class="admin-login-page">
<div class="center-card card admin-login">
<span class="brand"><span class="brand-icon">C</span><span>CBT ADMIN<small>Panel Panitia</small></span></span>
<h1>Login panitia</h1>
<p class="muted">Kelola mata pelajaran, peserta, soal, dan sesi lomba melalui server lokal.</p>
<form action="/admin/login" method="post">@csrf<label>Email<span class="input-icon"><x-icon name="mail"/><input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus></span>
</label>
<label>Password<span class="input-icon"><x-icon name="lock"/><input type="password" name="password" required autocomplete="current-password"></span>
</label>
<button class="wide">Masuk <x-icon name="arrow"/></button>
</form>
<p class="login-footer small muted"><a href="/">← Kembali ke login peserta</a></p>
</div>
</div>@endsection
