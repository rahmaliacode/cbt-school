@extends('layout')
@section('title','Login Peserta · CBT Lomba')
@section('content')<div class="login-wrap">
<section class="login-intro">
<span class="brand">
<span class="brand-icon">C</span> CBT LOMBA</span>
<p class="eyebrow">COMPUTER BASED TEST</p>
<h1 class="student-event-title">Lomba 10 (sepuluh) Mata Pelajaran Jenjang SMP Se- Kabupaten Aceh Tenggara Tahun 2026</h1><p class="muted">Dinas Pendidikan dan Kebudayaan Kabupaten Aceh Tenggara</p>
<p>Pastikan nomor peserta dan token sesi sesuai kartu yang diberikan panitia.</p>
<div class="local-label">
<span class="status-dot">
</span> Ujian melalui jaringan lokal</div>
</section>
<section class="card login-form">
<p class="eyebrow">AKSES PESERTA</p>
<h2>Masuk sebagai peserta</h2>
<p class="muted">Masukkan data akses Anda.</p>
<form method="post" action="/login">@csrf<label>Nomor peserta<input name="participant_number" value="{{ old('participant_number') }}" placeholder="Contoh: P-001" required autocomplete="username">
</label>
<label>Token sesi<input name="token" required placeholder="Masukkan token dari panitia" autocomplete="off">
</label>
<button class="wide">Masuk ke ruang tunggu →</button>
</form>
<a class="small muted" href="/admin/login">Masuk sebagai panitia</a>
</section>
</div>@endsection
