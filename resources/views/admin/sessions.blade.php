@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">PENGELOLAAN</p>
<h1>Sesi lomba</h1>
</div>
<a class="button" href="/admin/sessions/create">+ Tambah sesi</a>
</div>
<div class="card">@include('admin.session-table')</div>@endsection
