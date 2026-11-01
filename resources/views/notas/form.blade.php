@extends('layouts.app')
@section('titulo', $nota->exists ? 'Editar nota' : 'Nova nota')
@section('conteudo')
<div class="page-heading"><div><div class="eyebrow">NOTAS</div><h1>{{ $nota->exists ? 'Editar nota' : 'Nova nota' }}<span>.</span></h1><p>Texto simples, salvo no seu servidor.</p></div></div>
@if ($errors->any())
<div class="auth-error" role="alert"><ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
@endif
<form class="note-editor" method="POST" action="{{ $nota->exists ? route('notas.update', $nota->id, false) : route('notas.store', [], false) }}">
  @csrf
  @if ($nota->exists) @method('PUT') @endif
  <label for="note-title">Título</label>
  <input id="note-title" name="titulo" required maxlength="120" value="{{ old('titulo', $nota->titulo) }}" autofocus />
  <label for="note-content">Nota</label>
  <textarea id="note-content" name="conteudo" required maxlength="20000" rows="14">{{ old('conteudo', $nota->conteudo) }}</textarea>
  <p class="notes-hint">Até 20.000 caracteres. As alterações só são gravadas ao salvar.</p>
  <div class="note-actions"><button class="button primary" type="submit">Salvar nota</button><a class="button" href="{{ route('notas.index', [], false) }}">Cancelar</a></div>
</form>
@endsection
