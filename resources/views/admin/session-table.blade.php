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
<span class="pill {{ $s->status }}">{{ ['draft'=>'Belum dimulai','running'=>'Berlangsung','ended'=>'Berakhir'][$s->status] }}</span>
</td>
<td>{{ $s->question_count }} soal · {{ $s->duration_minutes }} menit</td>
<td>
<div class="actions table-actions">
<a class="action-button" href="/admin/sessions/{{ $s->id }}/monitor"><x-icon name="monitor"/>Monitor</a>
<a class="action-button" href="/admin/sessions/{{ $s->id }}/results"><x-icon name="chart"/>Hasil</a>@if($s->status==='draft')<a class="action-button" href="/admin/sessions/{{ $s->id }}/edit"><x-icon name="settings"/>Pengaturan</a>
<form method="post" action="/admin/sessions/{{ $s->id }}/start" data-confirm="Mulai sesi sekarang? Konfigurasi dan soal akan dikunci.">@csrf<button class="small-button"><x-icon name="play"/>Mulai</button>
</form>@elseif($s->status==='running')<form method="post" action="/admin/sessions/{{ $s->id }}/end" data-confirm="Akhiri sesi? Semua jawaban peserta akan dikumpulkan dan dikunci.">@csrf<button class="danger small-button"><x-icon name="stop"/>Akhiri</button>
</form>@endif @if($s->status!=='draft')<button type="button" class="action-button" data-toggle-row="session-reset-{{ $s->id }}"><x-icon name="reset"/>Reset sesi</button>@endif</div>
</td>
</tr>@if($s->status!=='draft')<tr id="session-reset-{{ $s->id }}" class="row-form" data-search-skip hidden>
<td colspan="5"><form method="post" action="/admin/sessions/{{ $s->id }}/reset" class="inline-row-form" data-confirm="Reset sesi {{ $s->name }}? Semua jawaban dan nilai peserta di sesi ini dihapus dan sesi kembali ke status belum dimulai.">@csrf<label>Alasan reset (tercatat di audit)<input name="reason" required minlength="5" maxlength="500" placeholder="Misalnya: ulang simulasi"></label>
<button class="danger small-button"><x-icon name="reset"/>Reset sesi</button>
</form></td>
</tr>@endif @empty<tr>
<td colspan="5" class="empty">Belum ada sesi. Buat sesi lomba pertama.</td>
</tr>@endforelse</tbody>
</table>
</div>
