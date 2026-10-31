@extends('layouts.base')

@section('titulo', 'Entrar')

@section('corpo')
    <main class="auth-page" id="main">
      <div class="auth-card">
        <div class="auth-brand">
          <span class="brand-mark"><x-icone nome="shield" /></span>
          <span class="brand-name">{{ config('painel.nome') }}<span class="brand-dot">.</span></span>
        </div>
        <div class="eyebrow">ACESSO PRIVADO</div>
        <h1>Entrar<span>.</span></h1>
        <p class="auth-sub">Este painel é pessoal. Não há cadastro público nem recuperação por e-mail.</p>

        @php($mensagem = $erro ?? session('erro'))
        @if ($mensagem)
          <div class="auth-error" role="alert">{{ $mensagem }}</div>
        @endif

        <form method="POST" action="{{ route('login.enviar', [], false) }}" class="auth-form">
          @csrf
          <label>
            E-mail
            <input type="email" name="email" value="{{ $email ?? old('email') }}" required maxlength="254" autocomplete="username" autofocus />
          </label>
          <label>
            Senha
            <input type="password" name="password" required maxlength="1024" autocomplete="current-password" />
          </label>
          <button type="submit" class="button primary auth-submit">Entrar</button>
        </form>
      </div>
    </main>
@endsection
