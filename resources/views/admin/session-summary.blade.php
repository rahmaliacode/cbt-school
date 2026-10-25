<dl class="session-summary">
<div><dt>Mata pelajaran</dt><dd>{{ $summarySession->subject->name }}</dd></div>
<div><dt>Peserta</dt><dd>{{ $summarySession->participants->count() }} peserta</dd></div>
<div><dt>Soal / waktu</dt><dd>{{ $summarySession->question_count }} soal · {{ $summarySession->duration_minutes }} menit</dd></div>
<div><dt>Pilihan jawaban</dt><dd>{{ $summarySession->option_count }} opsi (A–{{ $summarySession->option_count===5?'E':'D' }})</dd></div>
<div><dt>Tie-break</dt><dd>{{ ['none'=>'Nonaktif · peringkat seri','correct_count'=>'Jumlah benar terbanyak','earliest_submit'=>'Durasi tercepat'][$summarySession->tie_breaker] }}</dd></div>
<div><dt>Leaderboard live</dt><dd>{{ $summarySession->show_live_score?'Setelah peserta submit':'Setelah sesi berakhir' }}</dd></div>
</dl><div class="rule-banner"><p>Rumus penilaian</p><strong>Benar @score($summarySession->correct_score) · Salah @score($summarySession->incorrect_score) · Kosong @score($summarySession->blank_score)</strong></div>
<p class="small muted">Acak soal: {{ $summarySession->shuffle_questions?'Aktif':'Nonaktif' }} · Acak opsi: {{ $summarySession->shuffle_options?'Aktif':'Nonaktif' }}</p>
