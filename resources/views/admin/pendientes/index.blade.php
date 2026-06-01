@extends('layouts.admin')
@section('title', 'Pendientes')
@section('header_title', 'Pendientes de parada')

@section('content')

<div id="pendientes-content" class="space-y-6">
    @include('admin.pendientes.partials.content')
</div>

@push('scripts')
<script>
(function () {
    const CONTENT = document.getElementById('pendientes-content');
    let _refreshTimer = null;

    /* ── Transición de salida ─────────────────────────────── */
    function fadeOut() {
        CONTENT.style.transition    = 'opacity 0.15s ease, transform 0.15s ease';
        CONTENT.style.opacity       = '0.25';
        CONTENT.style.transform     = 'translateY(6px)';
        CONTENT.style.pointerEvents = 'none';
    }

    /* ── Transición de entrada ────────────────────────────── */
    function fadeIn() {
        CONTENT.style.transition = 'none';
        CONTENT.style.opacity    = '0';
        CONTENT.style.transform  = 'translateY(14px)';
        CONTENT.offsetHeight;    // fuerza reflow
        CONTENT.style.transition    = 'opacity 0.22s ease, transform 0.22s ease';
        CONTENT.style.opacity       = '1';
        CONTENT.style.transform     = 'translateY(0)';
        CONTENT.style.pointerEvents = '';
    }

    /* ── Auto-refresco: activa/cancela según estado de parada ─ */
    function syncRefreshTimer() {
        clearInterval(_refreshTimer);
        _refreshTimer = null;

        if (CONTENT.querySelector('[data-parada-activa]')) {
            _refreshTimer = setInterval(() => navigateTo(window.location.href), 60_000);
        }
    }

    /* ── Navegación sin recarga ───────────────────────────── */
    async function navigateTo(url) {
        fadeOut();

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) throw new Error('network');
            const data = await res.json();

            await new Promise(r => setTimeout(r, 130));

            CONTENT.innerHTML = data.html;

            if (window.lucide) window.lucide.createIcons();
            if (window.Alpine) window.Alpine.initTree(CONTENT);

            history.pushState({}, '', url);

            fadeIn();
            syncRefreshTimer();  // activa o cancela el intervalo según el nuevo contenido
        } catch (_) {
            window.location.href = url;
        }
    }

    /* ── Delegación de clics ─────────────────────────────── */
    CONTENT.addEventListener('click', function (e) {
        const link = e.target.closest('[data-nav-link]');
        if (!link) return;
        e.preventDefault();
        navigateTo(link.href);
    });

    /* ── Botón atrás/adelante ────────────────────────────── */
    window.addEventListener('popstate', () => navigateTo(window.location.href));

    /* ── Activar timer en la carga inicial si ya hay parada activa ── */
    syncRefreshTimer();
})();
</script>
@endpush

@endsection
