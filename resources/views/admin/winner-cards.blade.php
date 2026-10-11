<div class="winner-grid">@foreach($champions as $card)<section class="card winner-card"><p class="eyebrow">JUARA 1 · {{ $card['subject']->name }}</p>
@if($card['session'])<span class="pill">FINAL{{ $card['winners']->count()>1?' · SERI':'' }}</span><h2>{{ $card['session']->name }}</h2><p class="small muted">{{ $card['count'] }} peserta dinilai · {{ $card['session']->results_locked_at->format('d/m/Y H:i') }}</p>
@forelse($card['winners'] as $winner)<div class="winner-person"><strong>{{ $winner->attempt->participant->name }}</strong><span>{{ $winner->attempt->participant->school ?: 'Sekolah belum diisi' }}</span><b>{{ $winner->total_score }} <small>poin</small></b></div>@empty<p>Belum ada peserta dengan hasil.</p>@endforelse
<p class="small muted">Tie-break: {{ ['none'=>'Peringkat seri','correct_count'=>'Jumlah benar','earliest_submit'=>'Durasi pengerjaan tercepat'][$card['session']->configuration_snapshot['tie_breaker']??'none'] }}</p><a href="/admin/sessions/{{ $card['session']->id }}/results">Lihat peringkat lengkap →</a>
@else<h2>Belum ada juara</h2><p class="muted">Akhiri sesi, verifikasi nilai, lalu kunci hasil FINAL.</p>@endif
</section>@endforeach</div>
