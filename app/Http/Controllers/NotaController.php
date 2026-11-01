<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        $campo = in_array($request->query('ordenar'), ['created_at', 'updated_at'], true)
            ? $request->query('ordenar') : 'updated_at';
        $direcao = in_array($request->query('direcao'), ['asc', 'desc'], true)
            ? $request->query('direcao') : 'desc';

        $notas = Nota::query()->where('user_id', $request->user()->id)
            ->orderBy($campo, $direcao)->orderBy('id', $direcao)
            ->paginate(24)->appends(['ordenar' => $campo, 'direcao' => $direcao]);

        return view('notas.index', compact('notas', 'campo', 'direcao'));
    }

    public function create()
    {
        return view('notas.form', ['nota' => new Nota]);
    }

    public function store(Request $request)
    {
        $nota = new Nota($this->dados($request));
        $nota->user_id = $request->user()->id;
        $nota->save();

        return redirect()->route('notas.index')->with('sucesso', 'Nota criada.');
    }

    public function edit(Request $request, int $nota)
    {
        return view('notas.form', ['nota' => $this->propria($request, $nota)]);
    }

    public function update(Request $request, int $nota)
    {
        $registro = $this->propria($request, $nota);
        $registro->fill($this->dados($request));
        // Salvar sem mudanças não altera a data da última edição.
        if ($registro->isDirty()) {
            $registro->save();
        }

        return redirect()->route('notas.index')->with('sucesso', 'Nota salva.');
    }

    public function confirmDestroy(Request $request, int $nota)
    {
        return view('notas.excluir', ['nota' => $this->propria($request, $nota)]);
    }

    public function destroy(Request $request, int $nota)
    {
        $this->propria($request, $nota)->delete();

        return redirect()->route('notas.index')->with('sucesso', 'Nota excluída.');
    }

    private function propria(Request $request, int $id): Nota
    {
        return Nota::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function dados(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'conteudo' => ['required', 'string', 'max:20000'],
        ], [
            'titulo.required' => 'Informe um título.',
            'titulo.max' => 'O título deve ter até 120 caracteres.',
            'conteudo.required' => 'Escreva o conteúdo da nota.',
            'conteudo.max' => 'A nota deve ter até 20.000 caracteres.',
        ]);
    }
}
