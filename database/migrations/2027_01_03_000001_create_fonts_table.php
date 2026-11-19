<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fonts', function (Blueprint $t) {
            $t->id();
            $t->string('label', 60);
            $t->string('filename')->unique();
            $t->string('original_name');
            $t->unsignedInteger('size');
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonts');
    }
};
