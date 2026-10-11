<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','CBT Lomba')</title>
<link rel="stylesheet" href="/assets/app.css">
<script src="/assets/common.js" defer>
</script>@stack('head')</head>
<body class="{{ request()->is('admin*') && !request()->is('admin/login') ? 'admin-shell' : '' }}">
@if(request()->is('admin*') && !request()->is('admin/login'))<aside class="sidebar">
<a class="brand" href="/admin">
<span class="brand-icon">C</span> CBT ADMIN</a>
<p class="eyebrow">PANEL PANITIA</p>
<nav>@foreach(['/admin'=>'Dashboard','/admin/subjects'=>'Mata Pelajaran','/admin/participants'=>'Peserta','/admin/sessions'=>'Sesi Lomba','/admin/questions'=>'Paket Soal'] as $href=>$label)<a href="{{ $href }}" class="{{ request()->getPathInfo()===$href?'active':'' }}">{{ $label }}</a>@endforeach</nav>
<div class="sidebar-bottom">
<span class="status-dot">
</span> Server lokal · Offline ready<form method="post" action="/admin/logout">@csrf<button class="secondary">Keluar</button>
</form>
</div>
</aside>@endif
<main class="{{ request()->is('admin*') && !request()->is('admin/login') ? 'admin-main' : 'student-main' }}">@if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif @if($errors->any())<div class="notice error" role="alert">
<strong>Periksa kembali data Anda.</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif @yield('content')</main>@stack('scripts')</body>
</html>
