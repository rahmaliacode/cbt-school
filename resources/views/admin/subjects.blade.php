@extends('layout') @section('content')<p class="eyebrow">MASTER DATA</p>
<h1>Mata pelajaran</h1>
<div class="card">
<form method="post" action="/admin/subjects" class="inline-form">@csrf<label>Nama mapel<input name="name" required maxlength="100" placeholder="Contoh: Matematika">
</label>
<button>+ Tambah mapel</button>
</form>
<table>
<thead>
<tr>
<th>Mata pelajaran</th>
<th>Jumlah soal</th>
</tr>
</thead>
<tbody>@forelse($subjects as $subject)<tr>
<td>{{ $subject->name }}</td>
<td>{{ $subject->questions_count }}</td>
</tr>@empty<tr>
<td colspan="2">Belum ada mapel.</td>
</tr>@endforelse</tbody>
</table>
</div>@endsection
