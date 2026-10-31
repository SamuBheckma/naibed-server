<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->text('descricao')->nullable();
            $table->string('estado', 30)->default('ativo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
