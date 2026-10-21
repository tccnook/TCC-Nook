<?php
/** Ícones de interface locais; sem dependência de rede. */
function iconeEscrita(string $name, string $class=''): string
{
    $paths=[
        'arrow-right'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'arrow-left'=>'<path d="M19 12H5m6-6-6 6 6 6"/>',
        'pen'=>'<path d="m15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15l-1 6Z"/>',
        'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.9M16 3a4 4 0 0 1 0 8"/><circle cx="9" cy="7" r="4"/>',
        'chart'=>'<path d="M5 20V10m7 10V4m7 16V7"/>',
        'shield'=>'<path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/>',
        'book'=>'<path d="M12 6c-3-3-7-3-10-2v15c4-1 7-1 10 2 3-3 6-3 10-2V4c-4-1-7-1-10 2v15"/>',
        'spark'=>'<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5L12 2Z"/>',
        'leaf'=>'<path d="M20 3C9 2 3 6 4 13c1 8 13 8 16-10ZM3 22l12-13"/>',
        'heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'file'=>'<path d="M14 2H5v20h14V7l-5-5Zm0 0v6h6"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
        'save'=>'<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/>',
        'eye'=>'<path d="M2 12s4-8 10-8 10 8 10 8-4 8-10 8S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'send'=>'<path d="m22 2-7 20-4-9-9-4L22 2Zm0 0L11 13"/>',
        'upload'=>'<path d="M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6"/>',
        'info'=>'<circle cx="12" cy="12" r="10"/><path d="M12 11v6m0-10h.01"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'lock'=>'<rect x="4" y="10" width="16" height="12" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4m-4 5v3"/>',
        'chevron'=>'<path d="m6 9 6 6 6-6"/>',
        'undo'=>'<path d="m9 4-6 6 6 6M3 10h11a6 6 0 0 1 0 12"/>',
        'redo'=>'<path d="m15 4 6 6-6 6m6-6H10a6 6 0 0 0 0 12"/>',
        'list'=>'<path d="M9 6h12M9 12h12M9 18h12M3 6h.01M3 12h.01M3 18h.01"/>',
        'ordered'=>'<path d="M10 6h11m-11 6h11m-11 6h11M3 4h2v5M3 15c0-3 5-3 3 0l-3 4h4"/>',
        'align-left'=>'<path d="M3 4h18M3 9h12M3 14h18M3 19h12"/>',
        'align-center'=>'<path d="M3 4h18M6 9h12M3 14h18M6 19h12"/>',
        'align-right'=>'<path d="M3 4h18M9 9h12M3 14h18M9 19h12"/>',
        'align-justify'=>'<path d="M3 4h18M3 9h18M3 14h18M3 19h18"/>',
        'quote'=>'<path d="M3 12V6h6v6H3Zm0 0v6l6-6m6 0V6h6v6h-6Zm0 0v6l6-6"/>',
        'link'=>'<path d="m10 13 4-4m-6 7-2 2a4 4 0 0 1-6-6l4-4a4 4 0 0 1 6 0m4 0 2-2a4 4 0 0 1 6 6l-4 4a4 4 0 0 1-6 0"/>',
        'expand'=>'<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/>',
        'erase'=>'<path d="m16 3 6 6-12 12H4l-3-3L16 3ZM7 12l6 6m0 3h9"/>',
        'share'=>'<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m9 10 6-3m-6 7 6 3"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M7 2v6m10-6v6"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
    ];
    return '<svg class="writing-icon '.htmlspecialchars($class,ENT_QUOTES,'UTF-8').'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['book']).'</svg>';
}
