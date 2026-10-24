@extends('layout') @section('title','Ujian · CBT Lomba') @section('content')<div id="exam-app" data-attempt="{{ $attempt->id }}">
<header class="exam-header">
<div class="exam-title">
<span class="brand-icon">C</span>
<div>
<span class="eyebrow">CBT LOMBA</span>
<h2>{{ $attempt->examSession->name }}</h2>
</div>
</div>
<div class="exam-participant"><span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($attempt->participant->name,0,1)) }}</span><div><strong>{{ $attempt->participant->name }}</strong><small>{{ $attempt->participant->participant_number }}</small></div></div>
<div class="exam-timer"><small><x-icon name="clock"/> Sisa waktu</small><time id="timer" aria-label="Sisa waktu">--:--:--</time></div>
</header>
<div class="exam-columns">
<section class="question-panel">
<div class="question-top">
<p id="question-number" class="eyebrow">Memuat soal…</p>
<span id="save-status" role="status" aria-live="polite">Menghubungkan ke server…</span>
</div>
<p id="question-text" class="question-text">
</p>
<figure id="question-media" hidden><img id="question-image" class="question-image" alt=""><figcaption id="question-image-alt"></figcaption><button type="button" class="secondary" data-enlarge-image="question-image">Perbesar gambar</button></figure>
<fieldset id="options">
<legend class="sr-only">Pilih jawaban</legend>
</fieldset>
<div id="network-warning" class="notice error" hidden>Jawaban belum tersimpan ke server. Periksa koneksi LAN lalu <button type="button" id="retry-save">Coba simpan lagi</button>. Jangan tutup browser.</div>
<div class="exam-actions">
<button id="previous" class="secondary" type="button">← Sebelumnya</button>
<button id="next" type="button">Selanjutnya →</button>
<button id="finish-last" class="danger" type="button" hidden><x-icon name="check"/> Selesai & kumpulkan</button>
</div>
</section>
<aside class="question-navigation">
<h3>Navigasi soal</h3>
<div class="nav-progress">
<div class="progress-track"><div id="progress-fill" class="progress-fill"></div></div>
<p id="progress" class="muted small">
</p>
</div>
<div id="question-grid" class="question-grid">
</div>
<div class="legend">
<p>
<i class="answered">
</i> Sudah dijawab</p>
<p>
<i>
</i> Belum dijawab</p>
<p>
<i class="current">
</i> Sedang dibuka</p>
</div>
<div class="finish-early">
<button id="finish" class="secondary wide" type="button">Akhiri ujian</button>
</div>
</aside>
</div>
</div>
<dialog id="submit-dialog">
<div class="dialog-content">
<p class="eyebrow">KONFIRMASI</p>
<h2>Kumpulkan jawaban sekarang?</h2>
<p>Setelah dikumpulkan, jawaban tidak dapat diubah.</p>
<div class="stats">
<div>
<strong id="summary-answered">0</strong>Terjawab</div>
<div>
<strong id="summary-blank">0</strong>Belum dijawab</div>
</div>
<p id="submit-message" class="muted" role="status">
</p>
<div class="actions">
<button class="secondary" id="cancel-submit" type="button">Kembali ke ujian</button>
<button class="danger" id="confirm-submit" type="button">Yakin, kumpulkan</button>
</div>
</div>
</dialog>@endsection @push('scripts')<script src="/assets/exam-guard.js" defer></script><script src="/assets/exam.js" defer>
</script>@endpush
