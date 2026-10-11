@extends('layout') @section('content')<p class="eyebrow">MASTER DATA</p>
<h1>Peserta lomba</h1>
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
<section class="card">
<h2>Daftar peserta</h2>@forelse($participants as $p)<details>
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
<form method="post" action="/admin/participants/{{ $p->id }}" data-confirm="Hapus peserta ini?">@csrf @method('DELETE')<button class="danger small-button">Hapus</button>
</form>
</details>@empty<p class="empty">Belum ada peserta.</p>@endforelse</section>@endsection
