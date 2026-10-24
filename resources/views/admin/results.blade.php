@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">REKAP LOMBA</p>
<h1>Hasil & leaderboard <span class="pill {{ $session->results_locked_at ? 'final' : 'pending' }}">{{ $session->results_locked_at ? 'FINAL' : 'DRAFT' }}</span></h1>
<p>{{ $session->name }} · Tie-breaker: {{ ['none'=>'Peringkat seri','correct_count'=>'Jumlah benar','earliest_submit'=>'Durasi tercepat'][$session->configuration_snapshot['tie_breaker']??$session->tie_breaker] }}</p>
</div>
@if($scoresVisible)<a class="button secondary" href="/admin/sessions/{{ $session->id }}/export"><x-icon name="download"/>CSV</a> <a class="button secondary" href="/admin/sessions/{{ $session->id }}/export-excel"><x-icon name="download"/>Excel</a> <button type="button" class="secondary" onclick="window.print()"><x-icon name="print"/>Cetak / PDF</button>@endif
</div>@if(!$scoresVisible)<div class="notice">Publikasi skor live dimatikan. Seluruh nilai akan tersedia setelah sesi berakhir.</div>@endif @if($session->status!=='ended')<div class="notice">Peringkat sementara hanya mencakup peserta yang sudah submit. Peringkat final tersedia setelah sesi berakhir.</div>@endif @if($session->status==='ended')<section class="card">@if(!$session->results_locked_at)<p>Verifikasi hasil sebelum dikunci menjadi FINAL.</p><form method="post" action="/admin/sessions/{{ $session->id }}/lock-results" data-confirm="Kunci hasil setelah verifikasi?">@csrf<button><x-icon name="lock"/>Kunci hasil</button></form>@else<p>Hasil final dikunci pada {{ $session->results_locked_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB.</p><details><summary>Buka kembali hasil</summary><form method="post" action="/admin/sessions/{{ $session->id }}/unlock-results">@csrf<label>Alasan (tercatat di audit)<input name="reason" required minlength="5" maxlength="500"></label><button class="secondary">Buka kembali</button></form></details>@endif</section>@endif<div class="stats three">@foreach($results->take(3) as $row)<div>
<span>Peringkat {{ $row->rank }}</span>
<strong>{{ $row->total_score }}</strong>{{ $row->attempt->participant->name }}</div>@endforeach</div>
<div class="card table-wrap">
<table>
<thead>
<tr>
<th>Peringkat</th>
<th>Peserta</th>
<th>Benar</th>
<th>Salah</th>
<th>Kosong</th>
<th>Skor</th>
<th>Mulai</th>
<th>Submit</th>
<th>Status</th>
</tr>
</thead>
<tbody>@forelse($results as $row)<tr>
<td>{{ $row->rank }}</td>
<td>
<a href="/admin/attempts/{{ $row->attempt_id }}">{{ $row->attempt->participant->name }}</a>
<div class="muted small">{{ $row->attempt->participant->participant_number }}</div>
</td>
<td>{{ $row->correct_count }}</td>
<td>{{ $row->incorrect_count }}</td>
<td>{{ $row->blank_count }}</td>
<td>
<b>{{ $row->total_score }}</b>
</td>
<td>{{ $row->attempt->started_at->timezone('Asia/Jakarta')->format('H:i:s') }}</td>
<td>{{ $row->attempt->submitted_at->timezone('Asia/Jakarta')->format('H:i:s') }}</td>
<td>{{ $row->attempt->status }}</td>
</tr>@empty<tr>
<td colspan="9" class="empty">Belum ada hasil. Hasil muncul setelah peserta submit.</td>
</tr>@endforelse</tbody>
</table>
</div><a href="/admin/winners">Ringkasan juara per mapel →</a>@endsection
