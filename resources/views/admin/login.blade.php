@extends('layout') @section('content')<div class="center-card card">
<span class="brand">CBT ADMIN</span>
<h1>Login panitia</h1>
<p class="muted">Kelola lomba melalui server lokal.</p>
<form action="/admin/login" method="post">@csrf<label>Email<input type="email" name="email" required autocomplete="username">
</label>
<label>Password<input type="password" name="password" required autocomplete="current-password">
</label>
<button class="wide">Masuk</button>
</form>
<a href="/">Kembali ke login peserta</a>
</div>@endsection
