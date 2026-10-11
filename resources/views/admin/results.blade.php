@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">REKAP LOMBA</p>
<h1>Hasil & leaderboard</h1>
<p>{{ $session->name }} · Tie-breaker: {{ ['none'=>'Peringkat seri','correct_count'=>'Jumlah benar','earliest_submit'=>'Submit paling awal'][$session->configuration_snapshot['tie_breaker']??$session->tie_breaker] }}</p>
</div>
<a class="button secondary" href="/admin/sessions/{{ $session->id }}/export">↓ Export CSV (Excel)</a>
</div>@if($session->status!=='ended')<div class="notice">Peringkat sementara hanya mencakup peserta yang sudah submit. Peringkat final tersedia setelah sesi berakhir.</div>@endif<div class="stats three">@foreach($results->take(3) as $row)<div>
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
</div>@endsection
