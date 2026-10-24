@extends('layout') @section('title','Ruang Tunggu · CBT Lomba') @section('content')<div class="student-hero">
<span class="brand"><span class="brand-icon">C</span><span>CBT LOMBA<small>Computer Based Test</small></span></span>
<p class="student-event-banner">Lomba 10 (sepuluh) Mata Pelajaran Jenjang SMP Se- Kabupaten Aceh Tenggara Tahun 2026</p>
<p class="hero-organizer">Dinas Pendidikan dan Kebudayaan Kabupaten Aceh Tenggara</p>
</div>
<div class="student-page">
<div class="card waiting-card">
<div class="waiting-head">
<div>
<p class="eyebrow">RUANG TUNGGU</p>
<h1>{{ $session->name }}</h1>
</div>
<span class="pill {{ $session->status }}">{{ ['draft'=>'Belum dimulai','running'=>'Sedang berlangsung','ended'=>'Sudah berakhir'][$session->status] }}</span>
</div>
<div class="participant-card">
<span class="avatar avatar-lg" aria-hidden="true">{{ mb_strtoupper(mb_substr($participant->name,0,1)) }}</span>
<div><strong>{{ $participant->name }}</strong><small>{{ $participant->participant_number }}@if($participant->school) · {{ $participant->school }}@endif</small></div>
<span class="participant-verified"><x-icon name="shield"/> Terverifikasi</span>
</div>
<div class="stats three">
<div><span class="stat-icon"><x-icon name="doc"/></span>
<strong>{{ $session->question_count }}</strong>Soal</div>
<div><span class="stat-icon"><x-icon name="clock"/></span>
<strong>{{ $session->duration_minutes }}</strong>Menit</div>
<div><span class="stat-icon"><x-icon name="list"/></span>
<strong>{{ $session->option_count }}</strong>Opsi jawaban</div>
</div>
<div class="score-rule">
<span class="score-rule-title">Aturan penilaian</span>
<span class="score-chip correct">Benar <b>{{ $session->correct_score }}</b></span>
<span class="score-chip wrong">Salah <b>{{ $session->incorrect_score }}</b></span>
<span class="score-chip blank">Kosong <b>{{ $session->blank_score }}</b></span>
</div>
<h3 class="rule-heading">Tata tertib ujian</h3>
<ul class="rule-list">
<li><span><x-icon name="screen"/></span>Selama ujian, gunakan layar penuh dan tetap di halaman soal. Perpindahan tab/jendela dicatat untuk ditinjau panitia.</li>
<li><span><x-icon name="clock"/></span>Batas waktu berlaku untuk seluruh sesi. Peserta yang mulai terlambat mendapat sisa waktu sesi.</li>
<li><span><x-icon name="save"/></span>Jawaban disimpan otomatis setiap kali memilih opsi.</li>
</ul>
@if($session->status==='draft')<div class="waiting-status" role="status"><span class="spinner" aria-hidden="true"></span> Menunggu panitia memulai sesi…</div>@elseif($session->status==='running' && !$attempt)<div class="waiting-status ready" role="status"><span class="status-dot"></span> Sesi sudah dimulai. Silakan mulai ujian.</div>@endif
<div class="waiting-actions">@if($attempt && $attempt->status !== 'in_progress')<a class="button wide" href="/finished">Lihat konfirmasi selesai</a>@else<form action="/start" method="post">@csrf<button class="wide" @disabled($session->status!=='running')>{{ $attempt?'Lanjutkan ujian':'Mulai ujian' }} <x-icon name="arrow"/></button>
</form>@endif<form action="/logout" method="post">@csrf<button class="link-button"><x-icon name="logout"/> Keluar</button>
</form></div>
<p class="small muted waiting-refresh">Status diperbarui otomatis setiap 5 detik.</p>
</div>
</div>@endsection @push('scripts')<script>setTimeout(()=>location.reload(),5000);</script>@endpush
