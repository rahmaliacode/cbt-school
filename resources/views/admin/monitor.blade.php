@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">PEMANTAUAN PESERTA</p>
<h1>Monitor live</h1>
<p>{{ $session->name }} · Token <code>{{ $session->token }}</code>
</p>
</div>
<a class="button secondary" href="/admin/sessions/{{ $session->id }}/results">Hasil & peringkat</a>
</div>
<div class="notice">Isi jawaban, kunci, benar/salah, dan skor tidak ditampilkan di monitor.</div>
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
<th>Reset resmi</th>
</tr>
</thead>
<tbody id="monitor-body">
</tbody>
</table>
</div>@endsection @push('scripts')<script>window.monitorEndpoint='/admin/sessions/{{ $session->id }}/monitor-data';</script>
<script src="/assets/monitor.js" defer>
</script>@endpush
