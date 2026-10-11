<div class="table-wrap">
<table>
<thead>
<tr>
<th>Sesi / Mapel</th>
<th>Token</th>
<th>Status</th>
<th>Soal / Durasi</th>
<th>Akses</th>
</tr>
</thead>
<tbody>@forelse($sessions as $s)<tr>
<td>
<b>{{ $s->name }}</b>
<div class="muted small">{{ $s->subject->name }}</div>
</td>
<td>
<code>{{ $s->token }}</code>
</td>
<td>
<span class="badge">{{ ['draft'=>'Belum dimulai','running'=>'Berlangsung','ended'=>'Berakhir'][$s->status] }}</span>
</td>
<td>{{ $s->question_count }} soal · {{ $s->duration_minutes }} menit</td>
<td>
<div class="actions">
<a href="/admin/sessions/{{ $s->id }}/monitor">Monitor</a>
<a href="/admin/sessions/{{ $s->id }}/results">Hasil</a>@if($s->status==='draft')<a href="/admin/sessions/{{ $s->id }}/edit">Ubah</a>
<form method="post" action="/admin/sessions/{{ $s->id }}/start" data-confirm="Mulai sesi sekarang? Konfigurasi dan soal akan dikunci.">@csrf<button class="small-button">Mulai</button>
</form>@elseif($s->status==='running')<form method="post" action="/admin/sessions/{{ $s->id }}/end" data-confirm="Akhiri sesi? Semua jawaban peserta akan dikumpulkan dan dikunci.">@csrf<button class="danger small-button">Akhiri</button>
</form>@endif</div>
</td>
</tr>@empty<tr>
<td colspan="5" class="empty">Belum ada sesi. Buat sesi lomba pertama.</td>
</tr>@endforelse</tbody>
</table>
</div>
