@extends('layout') @section('content')<p class="eyebrow">MASTER DATA</p>
<h1>Peserta lomba</h1>
<section class="card">
<h2>Import siswa / peserta</h2>
<p>Unduh template, isi data, lalu preview sebelum disimpan. Nomor peserta harus unik. Maksimal 5000 baris.</p>
<a class="button secondary" href="/admin/participants/template">↓ Template Excel peserta</a>
<form method="post" action="/admin/participants/preview" enctype="multipart/form-data">@csrf
<label>File Excel / CSV<input type="file" name="file" accept=".xlsx,.xls,.csv" required></label>
<button>Preview import</button></form>
<details><summary>Atau paste tabel dari Excel</summary><form method="post" action="/admin/participants/preview">@csrf
<label>Nomor Peserta | Nama | Asal Sekolah<textarea name="paste" rows="6" required placeholder="P-004&#9;Peserta Contoh&#9;SMP Contoh"></textarea></label><button>Preview paste</button></form></details>
</section>
<section class="card">
<h2>Tambah peserta</h2>
<form method="post" action="/admin/participants">@csrf<div class="form-grid">
<label>Nomor peserta<input name="participant_number" required maxlength="50" placeholder="P-001">
</label>
<label>Nama peserta<input name="name" required maxlength="150">
</label>
<label>Asal sekolah<input name="school" maxlength="150">
</label>
</div>
<button>+ Tambah peserta</button>
</form>
</section>
<section class="card" data-search-table>
<div class="table-tools"><label><span class="sr-only">Cari peserta</span><input type="search" data-search-input placeholder="Cari nama, nomor, atau mapel…"></label><span class="small muted" data-search-count></span></div><p class="table-empty" data-search-empty hidden>Tidak ada data yang cocok. Coba kata lain.</p>
<h2>Daftar peserta</h2>@forelse($participants as $p)<details data-search-entry>
<summary>{{ $p->participant_number }} · {{ $p->name }} <span class="muted">{{ $p->school }}</span>
</summary>
<form method="post" action="/admin/participants/{{ $p->id }}">@csrf @method('PUT')<div class="form-grid">
<label>Nomor<input name="participant_number" value="{{ $p->participant_number }}" required>
</label>
<label>Nama<input name="name" value="{{ $p->name }}" required>
</label>
<label>Sekolah<input name="school" value="{{ $p->school }}">
</label>
</div>
<button>Simpan perubahan</button>
</form>
<form method="post" action="/admin/participants/{{ $p->id }}" data-confirm="Hapus peserta ini?">@csrf @method('DELETE')<button class="danger small-button"><x-icon name="trash"/>Hapus</button>
</form>
</details>@empty<p class="empty">Belum ada peserta.</p>@endforelse</section>@endsection
