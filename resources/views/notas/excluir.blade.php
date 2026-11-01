@extends('layouts.app')
@section('titulo', 'Excluir nota')
@section('conteudo')
<div class="page-heading"><div><div class="eyebrow">NOTAS</div><h1>Excluir nota<span>?</span></h1><p>Esta ação remove a nota permanentemente.</p></div></div>
<div class="note-editor">
  <h2>{{ $nota->titulo }}</h2>
  <p class="note-preview">{{ \Illuminate\Support\Str::limit($nota->conteudo, 350) }}</p>
  <form method="POST" action="{{ route('notas.destroy', $nota->id, false) }}">
    @csrf @method('DELETE')
    <div class="note-actions"><button class="button primary" type="submit">Confirmar exclusão</button><a class="button" href="{{ route('notas.index', [], false) }}">Cancelar</a></div>
  </form>
</div>
@endsection
