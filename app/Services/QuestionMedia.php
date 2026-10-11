<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class QuestionMedia {
    public static function response(?string $path) {
        abort_unless($path && preg_match('#^question-images/[A-Za-z0-9]+\.(jpg|jpeg|png|webp)$#', $path) && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
