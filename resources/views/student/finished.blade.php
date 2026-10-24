@extends('layout') @section('title','Ujian Selesai · CBT Lomba') @section('content')<div class="student-hero success">
<span class="brand"><span class="brand-icon">C</span><span>CBT LOMBA<small>Computer Based Test</small></span></span>
<p class="student-event-banner">Lomba 10 (sepuluh) Mata Pelajaran Jenjang SMP Se- Kabupaten Aceh Tenggara Tahun 2026</p>
</div>
<div class="student-page">
<div class="card center-card finished-card">
<div class="success-symbol"><x-icon name="check"/></div>
<p class="eyebrow">UJIAN SELESAI</p>
<h1>Jawaban telah diterima</h1>
<p class="muted">Terima kasih telah mengikuti lomba. Jawaban Anda sudah tersimpan di server dan tidak dapat diubah.</p>
<span class="pill final">{{ $attempt->status==='timed_out'?'Dikumpulkan otomatis':'Berhasil dikumpulkan' }}</span>
<dl class="finished-details">
<div><dt>Peserta</dt><dd>{{ $attempt->participant->name }} · {{ $attempt->participant->participant_number }}</dd></div>
<div><dt>Sesi</dt><dd>{{ $attempt->examSession->name }}</dd></div>
<div><dt>Waktu submit</dt><dd>{{ $attempt->submitted_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB</dd></div>
</dl>@if($attempt->result)<div class="result-score">
<span><x-icon name="award"/> Skor total</span>
<strong>{{ $attempt->result->total_score }}</strong>
</div>@else<div class="help-box"><x-icon name="help"/><span>Hasil akan diumumkan oleh panitia.</span></div>@endif<form action="/logout" method="post">@csrf<button class="secondary wide">Kembali ke halaman awal</button>
</form>
</div>
</div>@endsection
