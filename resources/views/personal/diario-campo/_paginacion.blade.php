{{-- Paginacion de la Vista completa del Diario de Campo, dentro de la
     franja superior (pedido 2026-09-28: todo en una sola franja). Mismo
     esquema que reportes preventivos: primera/anterior, ventana de 2
     paginas a cada lado, siguiente/ultima. $filas ya trae el query string
     de los filtros (FieldDiaryFullView, opcion "query"). --}}
@if ($filas->total() > 0)
    <span class="text-xs text-slate-500" title="Filas que se ven en esta página">
        {{ number_format($filas->firstItem()) }}–{{ number_format($filas->lastItem()) }}
    </span>
@endif

@if ($filas->lastPage() > 1)
    <div class="custom-pagination">
        @if ($filas->currentPage() > 1)
            <a class="page-btn" href="{{ $filas->url(1) }}" title="Primera página">«</a>
            <a class="page-btn" href="{{ $filas->previousPageUrl() }}" title="Página anterior">‹</a>
        @endif

        @for ($page = $paginaInicio; $page <= $paginaFin; $page++)
            @if ($page === $filas->currentPage())
                <span class="page-current">{{ $page }}</span>
            @else
                <a class="page-btn" href="{{ $filas->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($filas->currentPage() < $filas->lastPage())
            <a class="page-btn" href="{{ $filas->nextPageUrl() }}" title="Página siguiente">›</a>
            <a class="page-btn" href="{{ $filas->url($filas->lastPage()) }}" title="Última página">»</a>
        @endif
    </div>
@endif
