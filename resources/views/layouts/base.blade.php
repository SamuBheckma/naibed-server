<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#f6f7f3" />
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <meta name="referrer" content="no-referrer" />
    <title>@yield('titulo') · {{ config('painel.nome') }}</title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/icone.svg" />
    <link rel="stylesheet" href="/assets/css/painel.css?v={{ is_file($css = public_path('assets/css/painel.css')) ? filemtime($css) : 0 }}" />
    <script defer src="/assets/js/painel.js?v={{ is_file($js = public_path('assets/js/painel.js')) ? filemtime($js) : 0 }}"></script>
  </head>
  <body>
    @yield('corpo')
  </body>
</html>
