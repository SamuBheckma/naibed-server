@extends('layouts.app')

@section('titulo', 'Início')

@section('conteudo')
        <div class="demo-notice">
          <x-icone nome="info" />
          <div>
            <strong>Painel privado.</strong> Os atalhos levam aos serviços; este
            painel não mede nem exibe o estado do servidor. Cada serviço mantém
            o próprio login.
          </div>
          <span class="demo-tag">PRIVADO</span>
        </div>
        <div class="page-heading">
          <div>
            <div class="eyebrow">SEU PAINEL, EM UM SÓ LUGAR</div>
            <h1>Início<span>.</span></h1>
            <p>Acesse seus serviços a partir de um único lugar.</p>
          </div>
        </div>

        <div class="metrics metrics-2">
          <div class="metric">
            <div class="metric-label">Atalhos prontos <span><x-icone nome="link" /></span></div>
            <div class="metric-bottom"><div class="metric-value">{{ $configurados }}</div></div>
            <div class="metric-note">de {{ count($atalhos) }} configurados no servidor</div>
          </div>
          <div class="metric">
            <div class="metric-label">Projetos <span><x-icone nome="layers" /></span></div>
            <div class="metric-bottom"><div class="metric-value">{{ $totalProjetos }}</div></div>
            <div class="metric-note">registrados neste painel</div>
          </div>
        </div>

        <div class="section-title">
          <div>
            <h2>Atalhos</h2>
            <p>Configurados no servidor, sem editor no navegador.</p>
          </div>
        </div>

        <div class="monitor-grid">
          @forelse ($atalhos as $atalho)
            @if ($atalho['estado'] === \App\Support\Atalhos::OK)
              <a class="monitor-card shortcut" href="{{ $atalho['url'] }}" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer">
                <div class="monitor-head">
                  <span class="monitor-icon"><x-icone :nome="$atalho['icone']" /></span>
                  <span class="status success">Pronto</span>
                </div>
                <h3>{{ $atalho['titulo'] }}</h3>
                <p>{{ $atalho['descricao'] }}</p>
                <span class="text-button shortcut-open">Abrir em nova aba <x-icone nome="arrow" /></span>
              </a>
            @else
              <div class="monitor-card shortcut is-off" aria-disabled="true">
                <div class="monitor-head">
                  <span class="monitor-icon"><x-icone :nome="$atalho['icone']" /></span>
                  <span class="status partial">{{ $atalho['estado'] === \App\Support\Atalhos::INVALIDO ? 'Endereço inválido' : 'Endereço não configurado' }}</span>
                </div>
                <h3>{{ $atalho['titulo'] }}</h3>
                <p>{{ $atalho['descricao'] }}</p>
                <p class="shortcut-help">
                  @if ($atalho['estado'] === \App\Support\Atalhos::INVALIDO)
                    O endereço configurado não pôde ser usado. Informe um endereço
                    http:// ou https:// que o visitante consiga abrir; localhost e
                    127.0.0.1 não servem.
                  @else
                    Defina no servidor o endereço externo deste serviço (por
                    exemplo, o endereço onion). Enquanto isso, não há link.
                  @endif
                </p>
              </div>
            @endif
          @empty
            <div class="empty-state">
              <span><x-icone nome="link" /></span>
              <h3>Nenhum atalho configurado</h3>
              <p>Os atalhos são definidos no servidor.</p>
            </div>
          @endforelse
        </div>
@endsection
