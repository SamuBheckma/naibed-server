<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->text('conteudo');
            $table->timestamps();
            $table->index(['user_id', 'created_at', 'id']);
            $table->index(['user_id', 'updated_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
