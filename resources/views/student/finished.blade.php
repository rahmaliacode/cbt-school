@extends('layout') @section('content')<div class="center-card card">
<div class="success-symbol">✓</div>
<p class="eyebrow">UJIAN SELESAI</p>
<h1>Jawaban telah diterima</h1>
<p>{{ $attempt->participant->name }} · {{ $attempt->participant->participant_number }}</p>
<p>{{ $attempt->examSession->name }}</p>
<p>Waktu submit: <b>{{ $attempt->submitted_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB</b>
</p>
<span class="badge">{{ $attempt->status==='timed_out'?'Dikumpulkan otomatis':'Berhasil dikumpulkan' }}</span>@if($attempt->examSession->configuration_snapshot['show_live_score'])<div class="result-score">
<span>Skor total</span>
<strong>{{ $attempt->result->total_score }}</strong>
</div>@else<p class="muted">Hasil akan diumumkan oleh panitia.</p>@endif<form action="/logout" method="post">@csrf<button class="secondary wide">Kembali ke halaman awal</button>
</form>
</div>@endsection
