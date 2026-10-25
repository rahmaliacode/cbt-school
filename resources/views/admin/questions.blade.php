@extends('layout') @section('content')<div class="page-heading">
<div>
<p class="eyebrow">BANK SOAL</p>
<h1>Paket soal</h1>
<p class="muted">Import Excel, tempel massal, atau input satu per satu.</p>
</div>
<a class="button secondary" href="/admin/questions/template">↓ Download template Excel</a>
</div>
<div class="two-columns">
<section class="card">
<h2>Import Excel</h2>
<p class="muted small">Header: No | Mapel | Soal | Opsi A–E | Kunci. Opsi E boleh kosong. Buat mapel sebelum import.</p>
<form enctype="multipart/form-data" method="post" action="/admin/questions/preview">@csrf<label>File soal (.xlsx, .xls, .csv)<input name="file" type="file" required accept=".xlsx,.xls,.csv">
</label>
<button>Periksa & preview</button>
</form>
</section>
<section class="card">
<h2>Bulk paste</h2>
<p class="muted small">Salin sembilan kolom dari Excel, dipisahkan TAB. Header boleh disertakan.</p>
<form enctype="multipart/form-data" method="post" action="/admin/questions/preview">@csrf<label>Data soal<textarea name="paste" rows="4" required placeholder="Tempel baris dari Excel di sini">
</textarea>
</label>
<button>Preview data</button>
</form>
</section>
</div>
<details class="card">
<summary>+ Tambah soal manual</summary>
<form enctype="multipart/form-data" method="post" action="/admin/questions">@csrf @include('admin.question-fields',['q'=>null])<button>Simpan soal</button>
</form>
</details>
<section class="card" data-search-table>
<div class="table-tools"><label><span class="sr-only">Cari soal</span><input type="search" data-search-input placeholder="Cari teks soal atau nomor…"></label><span class="small muted" data-search-count></span></div><p class="table-empty" data-search-empty hidden>Tidak ada data yang cocok. Coba kata lain.</p>
<h2>Daftar soal <span class="muted small">{{ $questions->count() }} soal</span>
</h2>@php($bySubject = $questions->groupBy('subject_id'))@forelse($subjects as $subject)@php($items = $bySubject->get($subject->id, collect()))<details class="subject-group" @if($items->isNotEmpty()) open @endif>
<summary><strong>{{ $subject->name }}</strong> <span class="muted small">{{ $items->count() }} soal</span></summary>@if($items->isEmpty())<p class="empty">Belum ada soal untuk mapel ini.</p>@else<div class="table-wrap">
<table class="question-table">
<thead>
<tr>
<th>No</th>
<th>Soal</th>
<th>Opsi</th>
<th>Kunci</th>
<th>Gambar</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@foreach($items as $q)<tr>
<td>{{ $q->number }}</td>
<td>{{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($q->text), 140) }}</td>
<td>{{ $q->options->count() }}</td>
<td><strong>{{ $q->options->firstWhere('is_correct', true)?->label ?? '—' }}</strong></td>
<td>{{ $q->image_path ? 'Ada' : '—' }}</td>
<td><div class="actions table-actions"><button type="button" class="action-button" data-toggle-row="question-edit-{{ $q->id }}"><x-icon name="edit"/>Edit</button><form method="post" action="/admin/questions/{{ $q->id }}" data-confirm="Hapus soal ini?">@csrf @method('DELETE')<button class="danger small-button"><x-icon name="trash"/>Hapus</button>
</form></div></td>
</tr>
<tr id="question-edit-{{ $q->id }}" class="row-form" data-search-skip hidden>
<td colspan="6"><form enctype="multipart/form-data" method="post" action="/admin/questions/{{ $q->id }}">@csrf @method('PUT') @include('admin.question-fields')<button>Simpan perubahan</button>
</form></td>
</tr>@endforeach</tbody>
</table>
</div>@endif</details>@empty<p class="empty">Belum ada mapel. Buat mapel dahulu di menu Mata Pelajaran.</p>@endforelse</section>@endsection
