<?php

namespace App\Http\Controllers;

use App\Models\Projeto;

class ProjectController extends Controller
{
    public function index()
    {
        $projetos = Projeto::query()->orderByDesc('updated_at')->limit(100)->get();

        return view('projetos', ['projetos' => $projetos]);
    }
}
