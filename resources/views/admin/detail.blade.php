@extends('layout') @section('content')<p class="eyebrow">DETAIL HASIL PESERTA</p>
<h1>{{ $attempt->participant->name }}</h1>
<p>{{ $attempt->participant->participant_number }} · {{ $attempt->examSession->name }}</p>
<div class="stats">
<div>
<strong>@score($attempt->result->total_score)</strong>Skor</div>
<div>
<strong>{{ $attempt->result->correct_count }}</strong>Benar</div>
<div>
<strong>{{ $attempt->result->incorrect_count }}</strong>Salah</div>
<div>
<strong>{{ $attempt->result->blank_count }}</strong>Kosong</div>
</div>
<div class="card">@php($answers=$attempt->answers->keyBy('question_id')) @php($questions=collect($attempt->examSession->question_snapshot)->keyBy('id')) @foreach($attempt->question_order as $index=>$qid) @php($q=$questions[$qid]) @php($a=$answers->get($qid)) @php($selected=collect($q['options'])->firstWhere('id',$a?->answer_option_id)) @php($correct=collect($q['options'])->firstWhere('is_correct',true))<details>
<summary>{{ $index+1 }}. {{ $q['text'] }} <span class="pill {{ !$selected?'neutral':($selected['is_correct']?'final':'danger') }}">{{ !$selected?'Kosong':($selected['is_correct']?'Benar':'Salah') }}</span>
</summary>
@if(!empty($q['image_path']))<img class="question-image" src="/admin/questions/{{ $qid }}/image" alt="{{ $q['image_alt']??'Gambar pendukung soal' }}">@endif
<p>Jawaban peserta: {{ $selected['text']??'Tidak dijawab' }}</p>
<p>Kunci: {{ $correct['text'] }}</p>
</details>@endforeach</div>
<a href="/admin/sessions/{{ $attempt->exam_session_id }}/results">← Kembali ke hasil</a>@endsection
