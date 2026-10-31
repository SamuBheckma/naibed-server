<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Support\Atalhos;

class DashboardController extends Controller
{
    public function index()
    {
        $atalhos = Atalhos::todos();

        return view('dashboard', [
            'atalhos' => $atalhos,
            'configurados' => count(array_filter($atalhos, fn (array $a) => $a['estado'] === Atalhos::OK)),
            'totalProjetos' => Projeto::query()->count(),
        ]);
    }
}
