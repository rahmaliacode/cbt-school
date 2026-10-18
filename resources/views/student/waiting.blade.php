@extends('layout') @section('content')<div class="center-card card">
<p class="student-event-banner">Lomba 10 (sepuluh) Mata Pelajaran Jenjang SMP Se- Kabupaten Aceh Tenggara Tahun 2026</p>
<p class="eyebrow">RUANG TUNGGU</p>
<h1>{{ $session->name }}</h1>
<p>{{ $participant->name }} · {{ $participant->participant_number }}</p>
<span class="badge">{{ ['draft'=>'Belum dimulai','running'=>'Sedang berlangsung','ended'=>'Sudah berakhir'][$session->status] }}</span>
<div class="stats three">
<div>
<strong>{{ $session->question_count }}</strong>Soal</div>
<div>
<strong>{{ $session->duration_minutes }}</strong>Menit</div>
<div>
<strong>{{ $session->option_count }}</strong>Opsi</div>
</div>
<div class="score-rule">Benar <b>{{ $session->correct_score }}</b> · Salah <b>{{ $session->incorrect_score }}</b> · Kosong <b>{{ $session->blank_score }}</b>
</div>
<p class="notice">Selama ujian, gunakan layar penuh dan tetap di halaman soal. Perpindahan tab/jendela dicatat untuk ditinjau panitia.</p>
<p class="muted">Batas waktu berlaku untuk seluruh sesi. Peserta yang mulai terlambat mendapat sisa waktu sesi. Jawaban disimpan otomatis setiap kali memilih opsi.</p>@if($attempt && $attempt->status !== 'in_progress')<a class="button wide" href="/finished">Lihat konfirmasi selesai</a>@else<form action="/start" method="post">@csrf<button class="wide" @disabled($session->status!=='running')>{{ $attempt?'Lanjutkan ujian':'Mulai ujian' }}</button>
</form>@endif<form action="/logout" method="post">@csrf<button class="secondary wide">Keluar</button>
</form>
<p class="small muted">Status diperbarui otomatis setiap 5 detik.</p>
</div>@endsection @push('scripts')<script>setTimeout(()=>location.reload(),5000);</script>@endpush
