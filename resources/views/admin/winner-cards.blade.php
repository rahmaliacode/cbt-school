<div class="card table-wrap">
<table class="winner-table">
<thead>
<tr>
<th>Mapel</th>
<th>Juara 1</th>
<th>Asal sekolah</th>
<th>Poin</th>
<th>Sesi FINAL</th>
<th>Tie-break</th>
<th></th>
</tr>
</thead>
<tbody>@foreach($champions as $card)@if($card['session'] && $card['winners']->isNotEmpty())@foreach($card['winners'] as $winner)<tr>
@if($loop->first)<td rowspan="{{ $card['winners']->count() }}"><strong>{{ $card['subject']->name }}</strong>@if($card['winners']->count()>1)<br><span class="pill">SERI</span>@endif</td>@endif
<td><strong>{{ $winner->attempt->participant->name }}</strong><br><span class="small muted">{{ $winner->attempt->participant->participant_number }}</span></td>
<td>{{ $winner->attempt->participant->school ?: 'Sekolah belum diisi' }}</td>
<td><b>@score($winner->total_score)</b></td>
@if($loop->first)<td rowspan="{{ $card['winners']->count() }}">{{ $card['session']->name }}<br><span class="small muted">{{ $card['count'] }} peserta dinilai · dikunci {{ $card['session']->results_locked_at->format('d/m/Y H:i') }}</span></td>
<td rowspan="{{ $card['winners']->count() }}">{{ ['none'=>'Peringkat seri','correct_count'=>'Jumlah benar','earliest_submit'=>'Durasi pengerjaan tercepat'][$card['session']->configuration_snapshot['tie_breaker']??'none'] }}</td>
<td rowspan="{{ $card['winners']->count() }}"><a href="/admin/sessions/{{ $card['session']->id }}/results">Peringkat lengkap →</a></td>@endif
</tr>@endforeach @else<tr>
<td><strong>{{ $card['subject']->name }}</strong></td>
<td colspan="6" class="muted">@if($card['session'])Belum ada peserta dengan hasil.@else Belum ada juara — akhiri sesi, verifikasi nilai, lalu kunci hasil FINAL.@endif</td>
</tr>@endif @endforeach</tbody>
</table>
</div>
