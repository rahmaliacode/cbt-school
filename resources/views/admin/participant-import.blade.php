@extends('layout') @section('content')
<p class="eyebrow">VALIDASI IMPORT</p><h1>Preview peserta</h1>
@if($preview['errors'])<div class="notice error"><strong>Import dibatalkan sampai semua error diperbaiki.</strong><ul>@foreach($preview['errors'] as $e)<li>Baris {{ $e['row'] }}: {{ $e['message'] }}</li>@endforeach</ul></div>@endif
<section class="card table-wrap"><table><thead><tr><th>Nomor peserta</th><th>Nama</th><th>Asal sekolah</th></tr></thead><tbody>@foreach($preview['rows'] as $row)<tr><td>{{ $row['participant_number'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['school'] }}</td></tr>@endforeach</tbody></table></section>
@if(!$preview['errors'])<form method="post" action="/admin/participants/import" data-confirm="Simpan seluruh peserta hasil preview?">@csrf<button>Simpan {{ count($preview['rows']) }} peserta</button></form>@endif
<a href="/admin/participants">Kembali ke peserta</a>@endsection
