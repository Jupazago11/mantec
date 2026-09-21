<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Personal') · Gestión de Personal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak]{display:none!important}

        {{-- Mismo patron de tabla ancha que el resto de Mantec (y que el
             mockup preview-personal/_layout.blade.php, de donde se porto). --}}
        .table-scroll-container {
            overflow-x: auto;
            overflow-y: visible;
            scrollbar-width: none;
        }
        .table-scroll-container::-webkit-scrollbar { height: 0; }

        .preventive-table {
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }
        .preventive-table th,
        .preventive-table td { vertical-align: top; }
        .preventive-table th {
            white-space: normal;
            line-height: 1.05rem;
            vertical-align: middle;
        }
        .sticky-table-head th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgb(248 250 252);
            box-shadow: inset 0 -1px 0 rgb(226 232 240);
        }
        .sticky-table-head th.sticky-col,
        .preventive-table td.sticky-col {
            position: sticky;
            left: 0;
            z-index: 5;
            background: rgb(248 250 252);
        }
        .preventive-table td.sticky-col { background: white; z-index: 4; }

        {{-- Modo compacto para "Copiar como imagen" — ver
             app/Support (comentario completo en el mockup portado). --}}
        .exporting-compact .preventive-table th,
        .exporting-compact .preventive-table td {
            padding-block: 5px;
            padding-inline: 10px;
            font-size: 12.5px;
            line-height: 1.2;
        }
    </style>
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900">

    <div class="flex h-full flex-col">
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('personal_sidebar_collapsed') === '1',
                toggleSidebarCollapse() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('personal_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
                },
                showSidebar() {
                    this.sidebarCollapsed = false;
                    localStorage.setItem('personal_sidebar_collapsed', '0');
                }
            }"
            class="flex min-h-0 flex-1"
        >
            @include('components.personal.sidebar')

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                @include('components.personal.topbar')

                <main class="min-w-0 flex-1 overflow-y-auto p-4 md:p-8">
                    @if (session('success'))
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">
                            {{ session('success') }}
                        </div>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    {{-- Toast compartido de CRUD (crear/editar/archivar/etc.) — mismo
         componente que ya usan managed-users, measurements y los demas
         CRUDs del panel admin. Global a todo /personal/*, ver
         showCrudToast() mas abajo. --}}
    <div
        id="crudToast"
        class="fixed bottom-6 right-6 z-[100000] hidden max-w-md rounded-2xl border px-4 py-3 text-sm shadow-2xl transition"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <div id="crudToastIcon" class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"></div>
            <div class="min-w-0">
                <div id="crudToastTitle" class="font-semibold"></div>
                <div id="crudToastMessage" class="mt-0.5 whitespace-pre-line text-xs leading-relaxed"></div>
            </div>
        </div>
    </div>

    {{-- html2canvas-pro (no html2canvas a secas): Tailwind 4 emite colores
         oklch() y el html2canvas original no lo parsea. Ver comentario
         completo en preview-personal/_layout.blade.php (de donde se porto
         este mismo mixin) sobre por que ademas hace falta inlinear el CSS
         del clon. --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas-pro@2.4.2/dist/html2canvas-pro.min.js"></script>
    <script>
        function imageExporterMixin(filenamePrefix) {
            return {
                exportando: false,
                mensajeExport: null,
                async exportarComoImagen(fileLabel = null) {
                    if (this.exportando) return;
                    if (!window.html2canvas) {
                        this.mostrarMensaje('No se pudo cargar la librería de exportación (revisa la conexión/consola).');
                        console.error('exportarComoImagen: window.html2canvas no está definido — el script del CDN no cargó.');
                        return;
                    }
                    const el = document.getElementById('areaExportable');
                    if (!el) {
                        console.error('exportarComoImagen: no se encontró #areaExportable en esta página.');
                        return;
                    }
                    this.exportando = true;
                    const scrollBox = el.querySelector('.table-scroll-container, .scroll-container-visible');
                    const fullWidth = scrollBox ? scrollBox.scrollWidth : el.scrollWidth;
                    try {
                        const canvas = await window.html2canvas(el, {
                            backgroundColor: '#ffffff',
                            scale: 2,
                            windowWidth: fullWidth,
                            width: fullWidth,
                            onclone: (clonedDoc) => {
                                const area = clonedDoc.getElementById('areaExportable');
                                const box = area?.querySelector('.table-scroll-container, .scroll-container-visible');
                                box?.style.setProperty('overflow', 'visible');
                                box?.style.setProperty('max-height', 'none');
                                area?.classList.add('exporting-compact');

                                try {
                                    const cssText = Array.from(document.styleSheets)
                                        .map((sheet) => {
                                            try {
                                                return Array.from(sheet.cssRules).map((r) => r.cssText).join('\n');
                                            } catch (e) {
                                                return '';
                                            }
                                        })
                                        .join('\n');
                                    const styleTag = clonedDoc.createElement('style');
                                    styleTag.textContent = cssText;
                                    clonedDoc.head.appendChild(styleTag);
                                } catch (e) {
                                    console.warn('exportarComoImagen: no se pudo inlinear el CSS para el clon:', e);
                                }
                            },
                        });
                        canvas.toBlob(async (blob) => {
                            if (!blob) {
                                this.exportando = false;
                                this.mostrarMensaje('No se pudo generar la imagen (canvas vacío).');
                                console.error('exportarComoImagen: canvas.toBlob devolvió null.');
                                return;
                            }
                            // Fecha local del navegador (no toISOString(),
                            // que siempre convierte a UTC y puede saltar
                            // de dia respecto a la hora de Colombia).
                            const hoyLocal = (() => {
                                const d = new Date();
                                const pad2 = (n) => String(n).padStart(2, '0');
                                return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
                            })();
                            await this.copiarOdescargar(blob, `${filenamePrefix}-${fileLabel ?? hoyLocal}.png`);
                            this.exportando = false;
                        }, 'image/png');
                    } catch (err) {
                        this.exportando = false;
                        this.mostrarMensaje('No se pudo generar la imagen: ' + (err?.message || err));
                        console.error('exportarComoImagen: html2canvas lanzó un error:', err);
                    }
                },
                async copiarOdescargar(blob, filename) {
                    try {
                        if (!navigator.clipboard || !window.ClipboardItem) throw new Error('Este navegador no soporta escribir imágenes en el portapapeles');
                        await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                        this.mostrarMensaje('Imagen copiada — pégala directo en WhatsApp (Ctrl+V).');
                    } catch (err) {
                        console.warn('copiarOdescargar: no se pudo copiar al portapapeles, se descarga en su lugar:', err);
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        URL.revokeObjectURL(url);
                        this.mostrarMensaje('No se pudo copiar automáticamente: la imagen se descargó.');
                    }
                },
                mostrarMensaje(texto) {
                    this.mensajeExport = texto;
                    setTimeout(() => { this.mensajeExport = null; }, 5000);
                },
            };
        }

        {{-- Toast de CRUD compartido — mismo componente/comportamiento que
             resources/views/admin/managed-users/index.blade.php
             (showCrudToast), portado aqui para que todo /personal/* use la
             misma notificacion que el resto del panel admin. --}}
        let crudToastTimeout = null;

        function showCrudToast(message, type = 'success', title = null) {
            const toast = document.getElementById('crudToast');
            const icon = document.getElementById('crudToastIcon');
            const titleEl = document.getElementById('crudToastTitle');
            const messageEl = document.getElementById('crudToastMessage');

            if (!toast || !icon || !titleEl || !messageEl) {
                alert(Array.isArray(message) ? message.join('\n') : String(message || ''));
                return;
            }

            const normalizedMessage = Array.isArray(message)
                ? message.filter(Boolean).join('\n')
                : String(message || '');

            const isError = type === 'error';
            const isWarning = type === 'warning';

            toast.className = 'fixed bottom-6 right-6 z-[100000] max-w-md rounded-2xl border px-4 py-3 text-sm shadow-2xl transition';
            icon.className = 'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold';

            if (isError) {
                toast.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
                icon.classList.add('bg-red-600', 'text-white');
                icon.textContent = '!';
                titleEl.textContent = title || 'No fue posible completar la acción';
            } else if (isWarning) {
                toast.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
                icon.classList.add('bg-amber-500', 'text-white');
                icon.textContent = '!';
                titleEl.textContent = title || 'Revisa la información';
            } else {
                toast.classList.add('border-green-200', 'bg-green-50', 'text-green-800');
                icon.classList.add('bg-green-600', 'text-white');
                icon.textContent = '✓';
                titleEl.textContent = title || 'Acción completada';
            }

            messageEl.textContent = normalizedMessage;
            toast.classList.remove('hidden');

            clearTimeout(crudToastTimeout);
            crudToastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, isError ? 6500 : 3200);
        }

        {{-- Portado del mockup (preview-personal/_layout.blade.php) — usado
             por los tooltips de celda de Bitácora. --}}
        function posicionarPopover(el, ancho, altoEstimado = 110) {
            const rect = el.getBoundingClientRect();
            let left = rect.right - ancho;
            if (left < 8) left = 8;
            if (left + ancho > window.innerWidth - 8) left = window.innerWidth - ancho - 8;

            if (rect.bottom + 6 + altoEstimado <= window.innerHeight - 8) {
                const top = rect.bottom + 6;
                return `position:fixed; top:${top}px; left:${left}px; width:${ancho}px; z-index:9999;`;
            }

            // No cabe abajo con el alto estimado: anclar por "bottom" en vez de
            // calcular "top = rect.top - altoEstimado" (bug corregido
            // 2026-09-18). altoEstimado es un techo maximo (ej. 280 para
            // Personas), no el alto real del contenido — con una lista
            // filtrada a pocos resultados el popover real mide mucho menos,
            // y anclarlo por "top" asumiendo el maximo dejaba un hueco enorme
            // (el popover aparecia flotando lejos del campo que lo abrio,
            // cerca del tope del modal). Con "bottom" el borde inferior real
            // del popover queda pegado justo arriba del campo sin importar
            // cuanto mida su contenido.
            const bottom = window.innerHeight - rect.top + 6;
            return `position:fixed; bottom:${bottom}px; left:${left}px; width:${ancho}px; z-index:9999;`;
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); });
        document.addEventListener('alpine:init', () => { if (window.lucide) window.lucide.createIcons(); });
    </script>
    @stack('scripts')
</body>
</html>
