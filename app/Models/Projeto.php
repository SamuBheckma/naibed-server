<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base para a futura área de projetos. Nesta versão nada cria projetos:
 * a página apenas lista o que existir (e mostra um estado vazio honesto).
 */
class Projeto extends Model
{
    protected $table = 'projetos';

    protected $fillable = ['titulo', 'descricao', 'estado'];
}
