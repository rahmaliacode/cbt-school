<div class="form-grid">
<label>Mapel<select name="subject_id" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(($q?->subject_id??old('subject_id'))==$subject->id)>{{ $subject->name }}</option>@endforeach</select>
</label>
<label>Nomor soal<input type="number" name="number" min="1" required value="{{ $q?->number??old('number') }}">
</label>
<label>Kunci<select name="key">@foreach(range('A','E') as $label)<option @selected(($q?->options->firstWhere('is_correct',true)?->label??old('key','A'))===$label)>{{ $label }}</option>@endforeach</select>
</label>
</div>
<label>Teks soal<textarea name="text" rows="3" required>{{ $q?->text??old('text') }}</textarea>
</label>
<div class="form-grid">@foreach(range('A','E') as $label)<label>Opsi {{ $label }} {{ $label==='E'?'(opsional)':'' }}<textarea name="options[{{ $label }}]" rows="2" @required($label!=='E')>{{ $q?->options->firstWhere('label',$label)?->text??old('options.'.$label) }}</textarea>
</label>@endforeach</div>
