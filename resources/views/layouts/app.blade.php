@extends('layouts.base')

@section('corpo')
    @php
        $usuario = auth()->user();
        $inicial = mb_strtoupper(mb_substr((string) $usuario?->name, 0, 1)) ?: '?';
    @endphp
    <a class="skip" href="#main">Ir para o conteúdo</a>
    <div class="mobile-shade" id="mobile-shade"></div>
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="{{ route('inicio', [], false) }}" aria-label="{{ config('painel.nome') }}, início"
        ><span class="brand-mark"><x-icone nome="shield" /></span
        ><span class="brand-text"
          ><span class="brand-name">{{ config('painel.nome') }}<span class="brand-dot">.</span></span
          ><small>CENTRAL PRIVADA</small></span
        ></a
      >
      <div class="nav-label">WORKSPACE PESSOAL</div>
      <nav aria-label="Navegação principal">
        <a href="{{ route('inicio', [], false) }}" class="{{ request()->routeIs('inicio') ? 'active' : '' }}" @if (request()->routeIs('inicio')) aria-current="page" @endif
          ><i><x-icone nome="grid" /></i>Início</a
        >
        <a href="{{ route('projetos', [], false) }}" class="{{ request()->routeIs('projetos') ? 'active' : '' }}" @if (request()->routeIs('projetos')) aria-current="page" @endif
          ><i><x-icone nome="layers" /></i>Projetos</a
        >
        <a href="{{ route('notas.index', [], false) }}" class="{{ request()->routeIs('notas.*') ? 'active' : '' }}" @if (request()->routeIs('notas.*')) aria-current="page" @endif><i><x-icone nome="layers" /></i>Notas</a>
      </nav>
      <div class="sidebar-bottom">
        <div class="prototype-card">
          <span><x-icone nome="lock" /></span>
          <strong>Acesso privado</strong>
          <p>
            Este painel é só seu. Cada serviço listado mantém o próprio login e
            continua independente.
          </p>
        </div>
        <form method="POST" action="{{ route('sair', [], false) }}" class="logout-form">
          @csrf
          <button type="submit" class="nav-logout"><i><x-icone nome="logout" /></i>Sair</button>
        </form>
        <div class="profile">
          <span class="avatar">{{ $inicial }}</span>
          <div>
            <strong>{{ $usuario?->name }}</strong
            ><small>Administrador</small>
          </div>
          <span><x-icone nome="lock" /></span>
        </div>
      </div>
    </aside>
    <div class="app-shell">
      <header class="topbar">
        <div class="breadcrumb">
          <button
            type="button"
            class="icon-button mobile-menu"
            id="menu-button"
            aria-label="Abrir navegação"
            aria-expanded="false"
          >
            <x-icone nome="menu" /></button
          ><span class="crumb-icon"><x-icone nome="layers" /></span
          ><span>{{ config('painel.nome') }}</span><span class="slash">/</span
          ><strong>@yield('titulo')</strong>
        </div>
        <div class="topbar-right">
          <span class="privacy"><x-icone nome="lock" /> Privado</span
          ><span class="topbar-divider"></span
          ><span class="today">{{ now()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y') }}</span
          ><span class="top-avatar">{{ $inicial }}</span>
        </div>
      </header>
      <main id="main" tabindex="-1">
        @yield('conteudo')
        <footer>
          <span class="footer-brand">{{ config('painel.nome') }}.</span
          ><span>Uma central simples para o que é seu.</span
          ><span><x-icone nome="lock" /> Painel privado</span>
        </footer>
      </main>
    </div>
@endsection
