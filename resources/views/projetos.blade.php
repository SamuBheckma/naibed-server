@extends('layouts.app')

@section('titulo', 'Projetos')

@section('conteudo')
        <div class="page-heading">
          <div>
            <div class="eyebrow">ORGANIZAÇÃO</div>
            <h1>Projetos<span>.</span></h1>
            <p>Um lugar para acompanhar o que você está construindo.</p>
          </div>
        </div>

        @if ($projetos->isEmpty())
          <div class="empty-state">
            <span><x-icone nome="layers" /></span>
            <h3>Nenhum projeto ainda</h3>
            <p>
              Esta área está pronta para receber projetos, mas ainda não há
              nenhum registrado. Nada aqui é exemplo.
            </p>
          </div>
        @else
          <div class="monitor-grid">
            @foreach ($projetos as $projeto)
              <div class="monitor-card">
                <div class="monitor-head">
                  <span class="monitor-icon"><x-icone nome="layers" /></span>
                  <span class="status success">{{ $projeto->estado }}</span>
                </div>
                <h3>{{ $projeto->titulo }}</h3>
                <p>{{ $projeto->descricao }}</p>
                <div class="monitor-stats">
                  <span>Atualizado em<strong>{{ $projeto->updated_at?->format('d/m/Y H:i') }}</strong></span>
                </div>
              </div>
            @endforeach
          </div>
        @endif
@endsection
