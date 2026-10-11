<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('questions', function (Blueprint $table) { $table->string('image_path')->nullable(); $table->string('image_alt', 200)->nullable(); }); }
    public function down(): void { Schema::table('questions', fn (Blueprint $table) => $table->dropColumn(['image_path', 'image_alt'])); }
};
