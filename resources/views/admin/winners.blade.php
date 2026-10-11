@extends('layout') @section('content')
<div class="page-heading"><div><p class="eyebrow">RINGKASAN HASIL FINAL</p><h1>Juara per mapel</h1><p class="muted">Satu sesi per mapel. Default memakai hasil FINAL yang terakhir dikunci; peserta seri ditampilkan bersama.</p></div></div>
<form method="get" class="card"><div class="form-grid">@foreach($champions as $card)@if($card['choices']->isNotEmpty())<label>{{ $card['subject']->name }}<select name="session_ids[{{ $card['subject']->id }}]">@foreach($card['choices'] as $choice)<option value="{{ $choice->id }}" @selected($card['session']->id===$choice->id)>{{ $choice->name }}</option>@endforeach</select></label>@endif @endforeach</div><button>Tampilkan ringkasan</button></form>
@include('admin.winner-cards')
@endsection
