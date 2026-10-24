@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">PEMANTAUAN PESERTA</p>
<h1>Monitor live</h1>
<p>{{ $session->name }} · Token <code>{{ $session->token }}</code>
</p>
</div>
<div class="actions"><a class="button secondary" href="/admin/sessions/{{ $session->id }}/display" target="_blank" rel="noopener">Layar pantau (proyektor)</a><a class="button secondary" href="/admin/sessions/{{ $session->id }}/results">Hasil & peringkat</a></div>
</div>
<div class="stats"><div><strong id="monitor-waiting">—</strong>Belum mulai<small>Peserta belum membuka ujian</small></div><div><strong id="monitor-active">—</strong>Sedang mengerjakan<small>Pantau progres tanpa jawaban</small></div><div><strong id="monitor-submitted">—</strong>Sudah submit<small>Jawaban selesai dikumpulkan</small></div><div><strong id="monitor-disconnected">—</strong>Koneksi perlu diperiksa<small>Peserta aktif dengan koneksi terputus</small></div></div>
<div class="notice">Isi jawaban, kunci, benar/salah, dan skor tidak ditampilkan di monitor. Catatan fokus adalah sinyal dari browser, bukan bukti kecurangan; tinjau bersama peserta.</div>
<p id="monitor-status" role="status">Memuat data…</p>
<div class="card table-wrap">
<table>
<thead>
<tr>
<th>Peserta</th>
<th>Status</th>
<th>Progres</th>
<th>Sisa waktu</th>
<th>Mulai / Submit</th>
<th>Koneksi</th>
<th>Catatan fokus</th>
<th>Reset resmi</th>
</tr>
</thead>
<tbody id="monitor-body">
</tbody>
</table>
</div>@endsection @push('scripts')<script>window.monitorEndpoint='/admin/sessions/{{ $session->id }}/monitor-data';</script>
<script src="/assets/monitor.js" defer>
</script>@endpush
