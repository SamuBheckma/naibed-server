@extends('layouts.app')
@section('titulo', 'Notas')
@section('conteudo')
<div class="page-heading">
  <div><div class="eyebrow">SEU ESPAÇO DE ANOTAÇÕES</div>
    <h1>Notas<span>.</span></h1><p>Ideias e lembretes simples, sempre à mão.</p>
  </div>
  <a class="button primary" href="{{ route('notas.create', [], false) }}">+ Nova nota</a>
</div>
@if (session('sucesso'))
  <p class="notes-feedback" role="status">{{ session('sucesso') }}</p>
@endif
<form method="GET" action="{{ route('notas.index', [], false) }}" class="notes-toolbar">
  <label>Ordenar por
    <select name="ordenar">
      <option value="created_at" @selected($campo === 'created_at')>Data de criação</option>
      <option value="updated_at" @selected($campo === 'updated_at')>Última edição</option>
    </select>
  </label>
  <label>Ordem
    <select name="direcao">
      <option value="desc" @selected($direcao === 'desc')>Decrescente · recentes primeiro</option>
      <option value="asc" @selected($direcao === 'asc')>Crescente · antigas primeiro</option>
    </select>
  </label>
  <button class="button" type="submit">Aplicar</button>
  <a class="button" href="{{ route('notas.index', ['ordenar' => $campo, 'direcao' => $direcao === 'asc' ? 'desc' : 'asc'], false) }}" aria-label="Inverter ordem das notas">↕ Inverter ordem</a>
  <span class="notes-count">{{ $notas->total() }} {{ $notas->total() === 1 ? 'nota' : 'notas' }}</span>
</form>
<div class="notes-grid">
@forelse ($notas as $nota)
  <article class="note-card">
    <h2><a href="{{ route('notas.edit', $nota->id, false) }}">{{ $nota->titulo }}</a></h2>
    <p class="note-preview">{{ \Illuminate\Support\Str::limit($nota->conteudo, 350) }}</p>
    <div class="note-dates">
      <span>Criada <time datetime="{{ $nota->created_at->toIso8601String() }}">{{ $nota->created_at->format('d/m/Y H:i') }}</time></span>
      <span>Editada <time datetime="{{ $nota->updated_at->toIso8601String() }}">{{ $nota->updated_at->format('d/m/Y H:i') }}</time></span>
    </div>
    <div class="note-actions">
      <a class="button" href="{{ route('notas.edit', $nota->id, false) }}">Abrir / editar</a>
      <a class="text-button note-danger" href="{{ route('notas.confirm-destroy', $nota->id, false) }}">Excluir</a>
    </div>
  </article>
@empty
  <div class="empty-state"><h2>Sua primeira nota começa aqui</h2><p>Guarde uma ideia, um lembrete ou algo que queira consultar depois.</p><a class="button primary" href="{{ route('notas.create', [], false) }}">Criar nota</a></div>
@endforelse
</div>
@if ($notas->hasPages())
<nav class="notes-pagination" aria-label="Paginação das notas">
  @if ($notas->onFirstPage())
    <span>Anterior</span>
  @else
    <a class="button" href="{{ $notas->previousPageUrl() }}">Anterior</a>
  @endif
  <span>Página {{ $notas->currentPage() }} de {{ $notas->lastPage() }}</span>
  @if ($notas->hasMorePages())
    <a class="button" href="{{ $notas->nextPageUrl() }}">Próxima</a>
  @else
    <span>Próxima</span>
  @endif
</nav>
@endif
@endsection
