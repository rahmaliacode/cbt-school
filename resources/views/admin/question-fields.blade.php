<div class="form-grid">
<label>Mapel<select name="subject_id" required>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(($q?->subject_id??old('subject_id'))==$subject->id)>{{ $subject->name }}</option>@endforeach</select>
</label>
<label>Paket<select name="question_set">@foreach(\App\Models\Question::SETS as $value=>$label)<option value="{{ $value }}" @selected(($q?->question_set??old('question_set','main'))===$value)>{{ $label }}</option>@endforeach</select>
</label>
<label>Nomor soal<input type="number" name="number" min="1" required value="{{ $q?->number??old('number') }}">
</label>
<label>Kunci<select name="key">@foreach(range('A','E') as $label)<option @selected(($q?->options->firstWhere('is_correct',true)?->label??old('key','A'))===$label)>{{ $label }}</option>@endforeach</select>
</label>
</div>
<label>Teks soal<textarea name="text" rows="10" required placeholder="Teks bacaan dan pertanyaan. Tekan Enter untuk paragraf baru.">{{ $q?->text??old('text') }}</textarea>
</label>
<div class="form-grid">@foreach(range('A','E') as $label)<label>Opsi {{ $label }} {{ $label==='E'?'(opsional)':'' }}<textarea name="options[{{ $label }}]" rows="3" @required($label!=='E')>{{ $q?->options->firstWhere('label',$label)?->text??old('options.'.$label) }}</textarea>
</label>@endforeach</div>
<fieldset class="media-editor"><legend>Gambar soal (opsional)</legend><p class="small muted">JPG, PNG atau WebP, maksimal 2 MB. Setelah import Excel, lampirkan gambar lewat edit soal sebelum sesi dimulai.</p><label>Lampirkan gambar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-upload></label><label>Keterangan gambar<input name="image_alt" maxlength="200" value="{{ $q?->image_alt??old('image_alt') }}" placeholder="Misalnya: Diagram segitiga ABC"></label><img data-image-preview class="question-image" alt="Preview gambar soal" @if($q?->image_path) src="/admin/questions/{{ $q->id }}/image" @else hidden @endif>@if($q?->image_path)<label class="check"><input type="checkbox" name="remove_image" value="1"> Hapus gambar</label>@endif</fieldset>
