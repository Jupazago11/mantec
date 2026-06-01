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
            initFilter();
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

    /* ── Filtro del árbol de pendientes ──────────────────── */
    function initFilter() {
        const nameInput = document.getElementById('f-name');
        const areaSelect = document.getElementById('f-area');
        const typeSelect = document.getElementById('f-type');
        const clearBtn = document.getElementById('f-clear');
        const noResults = document.getElementById('f-no-results');

        if (!nameInput) return;

        function applyFilter() {
            const nameQ = nameInput.value.trim().toLowerCase();
            const areaQ = areaSelect ? areaSelect.value : '';
            const typeQ = typeSelect ? typeSelect.value : '';
            const hasFilter = nameQ || areaQ || typeQ;
            let totalVisible = 0;

            document.querySelectorAll('[data-area-name]').forEach(areaEl => {
                if (areaQ && areaEl.dataset.areaName !== areaQ) {
                    areaEl.hidden = true;
                    return;
                }

                let visibleEls = 0;
                areaEl.querySelectorAll('[data-element-name]').forEach(elEl => {
                    const nameMatch = !nameQ || elEl.dataset.elementName.toLowerCase().includes(nameQ);
                    const typeMatch = !typeQ || elEl.dataset.elementType === typeQ;
                    elEl.hidden = !(nameMatch && typeMatch);
                    if (!elEl.hidden) visibleEls++;
                });

                areaEl.hidden = visibleEls === 0;
                if (!areaEl.hidden) totalVisible += visibleEls;
            });

            if (noResults) noResults.hidden = !hasFilter || totalVisible > 0;
        }

        nameInput.addEventListener('input', applyFilter);
        if (areaSelect) areaSelect.addEventListener('change', applyFilter);
        if (typeSelect) typeSelect.addEventListener('change', applyFilter);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                nameInput.value = '';
                if (areaSelect) areaSelect.value = '';
                if (typeSelect) typeSelect.value = '';
                applyFilter();
            });
        }
    }

    initFilter();
})();
</script>
@endpush

@endsection
