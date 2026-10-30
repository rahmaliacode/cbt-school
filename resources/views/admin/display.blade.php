<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Layar Pantau Panitia · {{ $session->name }}</title>
<link rel="stylesheet" href="/assets/app.css">
<link rel="stylesheet" href="/assets/experience.css">
<link rel="stylesheet" href="/assets/theme.css">
<link rel="stylesheet" href="/assets/display.css">
<script src="/assets/common.js" defer></script>
</head>
<body class="display-screen">
<header class="display-header">
<div>
<p class="eyebrow">LAYAR PANTAU PANITIA · JANGAN DITAYANGKAN KE SISWA</p>
<h1>{{ $session->name }}</h1>
<p class="muted">{{ $session->subject->name }} · {{ $session->question_count }} soal</p>
</div>
<div class="display-timer"><small>Sisa waktu sesi</small><time id="display-timer">--:--:--</time></div>
</header>
<div class="display-counts">
<div><strong id="display-waiting">—</strong>Belum mulai</div>
<div><strong id="display-active">—</strong>Mengerjakan</div>
<div><strong id="display-submitted">—</strong>Selesai</div>
</div>
<p id="display-score-note" class="display-note" hidden>Skor ditandai <b>sementara</b> masih bisa berubah sampai siswa mengumpulkan jawaban.</p>
<table class="display-table">
<thead>
<tr>
<th id="display-first-head">No</th>
<th>Nomor peserta</th>
<th>Nama</th>
<th>Status</th>
<th>Dijawab</th>
<th id="display-score-head" hidden>Skor</th>
</tr>
</thead>
<tbody id="display-body">
</tbody>
</table>
<p id="display-status" class="display-status" role="status">Memuat data…</p>
<script>window.displayEndpoint='/admin/sessions/{{ $session->id }}/display-data';</script>
<script src="/assets/display.js" defer></script>
</body>
</html>
