@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">RINGKASAN LOMBA</p>
<h1>Dashboard panitia</h1>
<p class="muted">Siapkan sesi, pantau peserta, dan rekap hasil lomba.</p>
</div>
<a href="/admin/sessions/create" class="button">+ Buat sesi</a>
</div>
<div class="stats">@foreach($counts as $label=>$count)<div>
<strong>{{ $count }}</strong>{{ $label }}</div>@endforeach</div>
<section class="card">
<h2>Sesi lomba</h2>@include('admin.session-table')</section>
<div class="notice">Persiapan: buat mapel → daftarkan peserta → import soal → konfigurasi sesi → mulai sesi. Semua aset tersedia lokal.</div>@endsection
