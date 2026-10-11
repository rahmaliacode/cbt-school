@extends('layout') @section('content')<p class="eyebrow">VALIDASI IMPORT</p>
<h1>Preview soal</h1>
<p>{{ count($preview['rows']) }} baris valid · {{ count($preview['errors']) }} baris gagal.</p>@if($preview['errors'])<div class="notice error">
<strong>Import belum dapat disimpan. Tidak ada data yang dimasukkan.</strong>
<ul>@foreach($preview['errors'] as $error)<li>Baris {{ $error['row'] }}: {{ $error['message'] }}</li>@endforeach</ul>
</div>@endif<div class="card table-wrap">
<table>
<thead>
<tr>
<th>No</th>
<th>Mapel</th>
<th>Soal</th>
<th>Opsi</th>
<th>Kunci</th>
</tr>
</thead>
<tbody>@foreach($preview['rows'] as $q)<tr>
<td>{{ $q['number'] }}</td>
<td>{{ $q['subject'] }}</td>
<td>{{ $q['text'] }}</td>
<td>@foreach($q['options'] as $label=>$text)<div>{{ $label }}. {{ $text }}</div>@endforeach</td>
<td>{{ $q['key'] }}</td>
</tr>@endforeach</tbody>
</table>
</div>@if(!$preview['errors'])<form method="post" action="/admin/questions/import" data-confirm="Simpan seluruh soal hasil preview?">@csrf<button>Simpan {{ count($preview['rows']) }} soal</button>
</form>@endif<a href="/admin/questions">Kembali / perbaiki file</a>@endsection
