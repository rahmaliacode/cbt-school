@extends('layout')
@section('title','Login Peserta · CBT Lomba')
@section('content')<div class="login-wrap">
<section class="login-intro">
<span class="brand">
<span class="brand-icon">C</span><span>CBT LOMBA<small>Computer Based Test</small></span></span>
<div class="login-hero">
<span class="hero-tag"><span class="status-dot"></span> Lomba Mata Pelajaran 2026</span>
<h1 class="student-event-title">Lomba 10 (sepuluh) Mata Pelajaran Jenjang SMP Se- Kabupaten Aceh Tenggara Tahun 2026</h1>
<p class="login-organizer">Dinas Pendidikan dan Kebudayaan Kabupaten Aceh Tenggara</p>
</div>
<ul class="login-features">
<li><span><x-icon name="clock"/></span><div><strong>Waktu diatur server</strong><small>Semua peserta memakai batas waktu yang sama.</small></div></li>
<li><span><x-icon name="save"/></span><div><strong>Jawaban tersimpan otomatis</strong><small>Setiap pilihan langsung dikirim ke server.</small></div></li>
<li><span><x-icon name="wifi"/></span><div><strong>Jaringan lokal</strong><small>Ujian berjalan tanpa internet.</small></div></li>
</ul>
</section>
<section class="card login-form">
<div class="login-form-head">
<span class="login-form-icon"><x-icon name="user"/></span>
<p class="eyebrow">AKSES PESERTA</p>
<h2>Selamat datang, peserta</h2>
<p class="muted">Masukkan nomor peserta dan token sesi sesuai kartu yang diberikan panitia.</p>
</div>
<form method="post" action="/login">@csrf<label>Nomor peserta<span class="input-icon"><x-icon name="user"/><input name="participant_number" value="{{ old('participant_number') }}" placeholder="Contoh: P-001" required autocomplete="username" autocapitalize="characters" spellcheck="false" autofocus></span>
</label>
<label>Token sesi<span class="input-icon"><x-icon name="key"/><input name="token" required placeholder="Masukkan token dari panitia" autocomplete="off" autocapitalize="characters" spellcheck="false"></span>
<span class="field-hint">Token dibacakan atau ditulis oleh pengawas ruangan.</span>
</label>
<button class="wide">Masuk ke ruang tunggu <x-icon name="arrow"/></button>
</form>
<div class="help-box"><x-icon name="help"/><span>Kesulitan masuk? Angkat tangan dan hubungi pengawas ruangan.</span></div>
<p class="login-footer small muted">Panitia? <a href="/admin/login">Masuk ke panel panitia</a></p>
</section>
</div>
<p class="page-credit">Dinas Pendidikan dan Kebudayaan Kabupaten Aceh Tenggara · 2026</p>@endsection
