@extends('layout') @section('content')<p class="eyebrow">KONFIGURASI LOMBA</p>
<h1>{{ $session->exists?'Ubah sesi':'Buat sesi baru' }}</h1>
<p class="muted">Konfigurasi dan soal dikunci saat sesi dimulai.</p>
<div class="session-editor"><form data-session-form class="card" method="post" action="/admin/sessions{{ $session->exists?'/'.$session->id:'' }}">@csrf @if($session->exists) @method('PUT') @endif<div class="form-grid">
<label>Nama sesi<input name="name" required value="{{ old('name',$session->name) }}">
</label>
<label>Mata pelajaran<select name="subject_id" required>
<option value="">Pilih mapel</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(old('subject_id',$session->subject_id)==$subject->id)>{{ $subject->name }}</option>@endforeach</select>
</label>
<label>Paket soal<select name="question_set" id="question-set">@foreach(\App\Models\Question::SETS as $value=>$label)<option value="{{ $value }}" @selected(old('question_set',$session->question_set??'main')===$value)>{{ $label }}</option>@endforeach</select>
</label>
<label>Token sesi (6–32 karakter)<input name="token" minlength="6" maxlength="32" required value="{{ old('token',$session->token??strtoupper(\Illuminate\Support\Str::random(8))) }}">
</label>@foreach(['question_count'=>['Jumlah soal',1,500],'duration_minutes'=>['Durasi (menit)',1,600],'correct_score'=>['Skor benar',-10000,10000],'incorrect_score'=>['Skor salah',-10000,10000],'blank_score'=>['Skor kosong',-10000,10000]] as $field=>$spec)<label>{{ $spec[0] }}<input type="number" name="{{ $field }}" min="{{ $spec[1] }}" max="{{ $spec[2] }}" step="{{ str_contains($field,'score')?'0.01':'1' }}" required value="{{ old($field,$session->$field) }}">
</label>@endforeach<label>Jumlah opsi<select name="option_count">
<option value="4" @selected(old('option_count',$session->option_count)==4)>4 opsi (A–D)</option>
<option value="5" @selected(old('option_count',$session->option_count)==5)>5 opsi (A–E)</option>
</select>
</label>
<label>Tie-breaker<select name="tie_breaker">@foreach(['none'=>'Nonaktif (peringkat seri)','correct_count'=>'Jumlah benar terbanyak','earliest_submit'=>'Durasi pengerjaan tercepat'] as $value=>$label)<option value="{{ $value }}" @selected(old('tie_breaker',$session->tie_breaker)===$value)>{{ $label }}</option>@endforeach</select>
</label>
</div>
<fieldset>
<legend>Pengaturan tambahan</legend>@foreach(['shuffle_questions'=>'Acak urutan soal','shuffle_options'=>'Acak urutan opsi','show_live_score'=>'Leaderboard panitia saat sesi berjalan (peserta yang sudah submit)'] as $field=>$label)<label class="check">
<input type="checkbox" name="{{ $field }}" value="1" @checked(old($field,$session->$field))>{{ $label }}</label>@endforeach</fieldset>
<p class="small muted">Monitor panitia selalu menyembunyikan skor selama peserta mengerjakan. Paket memakai N soal pertama menurut nomor; acak mengubah urutan paket, bukan memilih soal lain.</p>
<fieldset>
<legend>Peserta sesi</legend>
<p class="small muted">Hanya peserta yang belum terdaftar di sesi lain dengan paket yang sama yang ditampilkan. Sesi Draw diisi peserta yang skornya seri di sesi Utama. <span id="taken-note"></span></p>
<label>Saring peserta<input type="search" id="participant-filter" placeholder="Ketik awalan nomor (mis. MTK-) atau nama"></label>
<label class="check">
<input type="checkbox" id="select-all">Pilih semua yang tampil <span class="small muted" id="participant-count"></span></label>
<div class="participant-picker">@forelse($participants as $p)<label class="check" data-participant data-taken="{{ $taken->filter(fn ($ids) => $ids->contains($p->id))->keys()->implode(' ') }}">
<input type="checkbox" name="participant_ids[]" value="{{ $p->id }}" @checked(in_array($p->id,old('participant_ids',$session->exists?$session->participants->pluck('id')->all():[])))>{{ $p->participant_number }} · {{ $p->name }}</label>@empty<p class="empty">Belum ada peserta.</p>@endforelse</div>
</fieldset>
<button @disabled($session->exists&&$session->status!=='draft')>Simpan sesi</button> <a href="/admin/sessions">Batal</a>
</form><aside class="session-preview"><section class="card" data-score-simulator><p class="eyebrow">PREVIEW PENILAIAN</p><h2 data-preview-title>Sesi baru</h2><p class="small muted" data-preview-meta></p><div class="rule-banner"><p>Simulasikan jumlah jawaban</p></div><div class="simulation-inputs"><label>Benar<input type="number" min="0" step="1" data-sample-correct aria-label="Jumlah benar contoh"></label><label>Salah<input type="number" min="0" step="1" data-sample-wrong aria-label="Jumlah salah contoh"></label></div><div class="score-output"><output aria-label="Skor simulasi" aria-live="polite">—</output><small>poin dari jawaban contoh</small></div><p class="simulation-equation" data-equation></p><p class="score-validation" role="status" data-validation></p><p class="small muted">Sisa soal dihitung kosong. Contoh ini tidak mengubah nilai peserta.</p></section><div class="ready-hint">Konfigurasi dan soal dikunci saat sesi dimulai. Sesi draf dapat diubah.</div></aside></div>@endsection @push('scripts')<script>(()=>{const items=[...document.querySelectorAll('[data-participant]')],filter=document.getElementById('participant-filter'),all=document.getElementById('select-all'),count=document.getElementById('participant-count'),set=document.getElementById('question-set'),note=document.getElementById('taken-note');const taken=i=>i.dataset.taken.split(' ').includes(set.value);const shown=()=>items.filter(i=>!i.hidden);const update=()=>{const q=filter.value.trim().toLowerCase();let hidden=0;items.forEach(i=>{const t=taken(i);if(t){hidden++;i.querySelector('input').checked=false;}i.hidden=t||!i.textContent.toLowerCase().includes(q);});note.textContent=hidden?hidden+' peserta sudah ada di sesi '+set.options[set.selectedIndex].text+' lain.':'';const v=shown();all.checked=v.length>0&&v.every(i=>i.querySelector('input').checked);count.textContent='('+v.filter(i=>i.querySelector('input').checked).length+' dari '+v.length+' tampil dipilih)';};filter.addEventListener('input',update);set.addEventListener('change',update);all.addEventListener('change',e=>{shown().forEach(i=>i.querySelector('input').checked=e.target.checked);update();});items.forEach(i=>i.querySelector('input').addEventListener('change',update));update();})();</script>@endpush
