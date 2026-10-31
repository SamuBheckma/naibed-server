@props(['nome' => 'info'])
@php
    // Ícones locais (mesmo traço do painel original). Os valores são constantes
    // internas; nada vindo do navegador entra neste mapa.
    $icones = [
        'shield' => '<path d="m10 2 7 3v6c0 5-7 8-7 8s-7-3-7-8V5z"/><path d="m6.5 10 2.5 2.5 4.5-5"/>',
        'grid' => '<rect x="3" y="3" width="5" height="5" rx="1"/><rect x="12" y="3" width="5" height="5" rx="1"/><rect x="3" y="12" width="5" height="5" rx="1"/><rect x="12" y="12" width="5" height="5" rx="1"/>',
        'layers' => '<path d="m10 2 8 4-8 4-8-4zm-8 9 8 4 8-4M2 15l8 4 8-4"/>',
        'lock' => '<rect x="4" y="9" width="12" height="9" rx="2"/><path d="M6 9V6a4 4 0 0 1 8 0v3M10 12v3"/>',
        'menu' => '<path d="M3 5h14M3 10h14M3 15h14"/>',
        'info' => '<circle cx="10" cy="10" r="7.5"/><path d="M10 9v5m0-8h.01"/>',
        'arrow' => '<path d="M4 10h12m-5-5 5 5-5 5"/>',
        'play' => '<circle cx="10" cy="10" r="7.5"/><path d="m8.5 7 4.5 3-4.5 3z"/>',
        'link' => '<path d="M8 12a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-1 1M12 8a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5l1-1"/>',
        'logout' => '<path d="M8 3H4v14h4M12 6l4 4-4 4M16 10H8"/>',
        'check' => '<path d="m4 10 4 4 8-9"/>',
    ];
    $conteudo = $icones[$nome] ?? $icones['info'];
@endphp
<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">{!! $conteudo !!}</svg>
