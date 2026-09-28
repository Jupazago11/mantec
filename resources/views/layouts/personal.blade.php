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
        {{-- La imagen exportada es para compartir/imprimir, no para
             interactuar — la columna de Acciones (lapiz/basura) no pinta
             nada ahi. .sticky-col es siempre esa columna (Acciones) en
             estas tablas, nunca otra cosa. --}}
        .exporting-compact .sticky-col {
            display: none;
        }

        {{-- Sin header en escritorio (pedido 2026-09-28): solo tenia el
             titulo (cada pantalla ya trae el suyo), nombre, "Volver al panel
             admin" y "Salir" — esos dos ultimos pasaron al pie del sidebar.
             Con el sidebar colapsado (ancho 0) el unico boton para reabrirlo
             vivia en el header: ahora es esta pestana fija en el borde
             izquierdo (solo lg+, via "hidden lg:inline-flex" + x-show). En
             movil queda una barra minima (lg:hidden) con el boton ☰. CSS
             propio, no clases Tailwind nuevas (el build de Vite no las
             tendria sin recompilar). --}}
        {{-- Sidebar sin parpadeo al recargar (pedido 2026-09-28: "al aplicar
             un filtro el sidebar se abre y se cierra"). Antes el ancho
             colapsado salia del :class de Alpine, que se carga con defer
             desde CDN: el navegador pintaba primero el sidebar abierto y
             luego Alpine lo cerraba con la transicion de 300ms. Ahora el
             estado inicial lo fija un script sincronico en <head> (clase
             personal-sidebar-collapsed en <html>, leida de localStorage)
             ANTES del primer pintado, y el ancho/visibilidad sale de este
             CSS, no de Alpine. La transicion solo existe con la clase
             personal-sidebar-anim, que se agrega despues de cargar la
             pagina: al recargar no hay nada que animar. --}}
        .personal-sidebar { transform: translateX(-100%); }
        .personal-sidebar.is-open { transform: none; }
        .personal-sidebar-anim .personal-sidebar { transition: transform 0.3s ease, width 0.3s ease; }

        @media (min-width: 1024px) {
            .personal-sidebar { transform: none; width: 18rem; }
            .personal-sidebar-collapsed .personal-sidebar {
                width: 0;
                min-width: 0;
                overflow: hidden;
                border-right-width: 0;
            }
            .personal-sidebar-collapsed .personal-sidebar-reopen { display: inline-flex; }
        }

        .personal-sidebar-reopen {
            display: none;
            position: fixed;
            top: 0.75rem;
            left: 0;
            z-index: 40;
            width: 1.5rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgb(226 232 240);
            border-left: 0;
            border-radius: 0 0.6rem 0.6rem 0;
            background: white;
            color: rgb(100 116 139);
            box-shadow: 0 4px 12px -6px rgb(15 23 42 / 0.3);
        }
        .personal-sidebar-reopen:hover { color: #d55b20; background: rgb(255 247 237); }

        {{-- Dialogo de confirmacion compartido (confirmarAccion(), abajo) —
             reemplaza el confirm() nativo del navegador para que las
             confirmaciones se vean como el resto del panel. --}}
        .personal-confirm { position: fixed; inset: 0; z-index: 100001; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgb(0 0 0 / 0.4); }
        .personal-confirm-peligro { background: rgb(220 38 38); color: white; }
        .personal-confirm-peligro:hover { background: rgb(185 28 28); }
    </style>
    <script>
        // Sincronico, dentro del head: fija el estado colapsado del sidebar
        // antes del primer pintado (ver .personal-sidebar arriba).
        (function () {
            var html = document.documentElement;
            try {
                if (localStorage.getItem('personal_sidebar_collapsed') === '1') {
                    html.classList.add('personal-sidebar-collapsed');
                }
            } catch (e) {}
            window.addEventListener('load', function () {
                requestAnimationFrame(function () { html.classList.add('personal-sidebar-anim'); });
            });
        })();
    </script>
</head>
<body class="h-screen overflow-hidden bg-slate-100 text-slate-900">

    <div class="flex h-full flex-col">
        <div
            x-data="{
                sidebarOpen: false,
                {{-- El estado real vive en la clase de <html> (script del
                     <head>), que es lo que lee el CSS; Alpine solo la
                     cambia y la guarda. --}}
                sidebarCollapsed: document.documentElement.classList.contains('personal-sidebar-collapsed'),
                setSidebarCollapsed(valor) {
                    this.sidebarCollapsed = valor;
                    document.documentElement.classList.toggle('personal-sidebar-collapsed', valor);
                    try { localStorage.setItem('personal_sidebar_collapsed', valor ? '1' : '0'); } catch (e) {}
                },
                toggleSidebarCollapse() {
                    this.setSidebarCollapsed(!this.sidebarCollapsed);
                },
                showSidebar() {
                    this.setSidebarCollapsed(false);
                }
            }"
            class="flex min-h-0 flex-1"
        >
            @include('components.personal.sidebar')

            <button
                type="button"
                @click="showSidebar()"
                class="personal-sidebar-reopen"
                title="Mostrar menú"
                aria-label="Mostrar menú"
            >
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
            </button>

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                @include('components.personal.topbar')

                {{-- main_class: una pantalla puede agregar una clase propia
                     (ej. Vista completa del Diario de Campo usa menos margen
                     y llena el alto disponible). --}}
                <main class="min-w-0 flex-1 overflow-y-auto p-4 md:p-8 @yield('main_class')">
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

    {{-- Dialogo de confirmacion compartido (ver confirmarAccion()). --}}
    <div id="confirmDialog" class="personal-confirm" role="dialog" aria-modal="true" aria-labelledby="confirmDialogTitle">
        <div class="w-full max-w-sm rounded-2xl bg-white p-5 text-left shadow-xl">
            <h3 id="confirmDialogTitle" class="text-sm font-bold text-slate-900"></h3>
            <p id="confirmDialogMessage" class="mt-2 whitespace-pre-line text-sm text-slate-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="confirmDialogCancel" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button type="button" id="confirmDialogOk" class="personal-confirm-peligro rounded-xl px-4 py-2 text-sm font-semibold"></button>
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
                async exportarComoImagen(fileLabel = null) {
                    if (this.exportando) return;
                    if (!window.html2canvas) {
                        this.mostrarMensaje('No se pudo cargar la librería de exportación (revisa la conexión/consola).', 'error');
                        console.error('exportarComoImagen: window.html2canvas no está definido — el script del CDN no cargó.');
                        return;
                    }
                    const el = document.getElementById('areaExportable');
                    if (!el) {
                        console.error('exportarComoImagen: no se encontró #areaExportable en esta página.');
                        return;
                    }
                    this.exportando = true;
                    const wrapper = el.querySelector('.compact-table-wrapper');
                    const scrollBox = el.querySelector('.table-scroll-container, .scroll-container-visible');
                    const table = el.querySelector('table');
                    // El modo compacto (exporting-compact: padding/fuente mas
                    // chicos, columna Acciones oculta) hay que aplicarlo a la
                    // pagina REAL antes de medir el ancho, no solo al clon en
                    // onclone() — si no, fullWidth queda calculado con el
                    // padding/columna grandes de antes, y el canvas termina
                    // mas ancho que el contenido compacto real, dejando una
                    // franja en blanco a la derecha de la imagen exportada.
                    el.classList.add('exporting-compact');
                    const prevOverflow = scrollBox?.style.overflow ?? '';
                    const prevMaxHeight = scrollBox?.style.maxHeight ?? '';
                    scrollBox?.style.setProperty('overflow', 'visible');
                    scrollBox?.style.setProperty('max-height', 'none');
                    // Ancho real de la TABLA (no del contenedor): #areaExportable,
                    // .compact-table-wrapper y .table-scroll-container son <div>
                    // de bloque con width:auto — por defecto se estiran al ancho
                    // de <main>, y probar a encogerlos con `width: fit-content`
                    // no funciono (html2canvas no soporta bien ese keyword de
                    // sizing intrinseco: la imagen exportada seguia con una
                    // franja en blanco a la derecha, confirmado con captura real
                    // 2026-09-21). Fix definitivo: medir el ancho de la tabla en
                    // pixeles concretos y fijar ESA misma cifra, tambien en
                    // pixeles, en los tres contenedores — sin depender de que el
                    // motor de layout resuelva ningun shrink-to-fit por su cuenta.
                    //
                    // Ojo con .preventive-table { min-width:100% }: si se mide
                    // el ancho de la tabla ANTES de encoger los contenedores,
                    // ese min-width todavia esta resolviendo contra el ancho
                    // completo de pagina de scrollBox, asi que table.scrollWidth
                    // saldria igual de inflado (el mismo bug, solo que medido
                    // en otro elemento). Se anula el min-width de la tabla justo
                    // antes de medir para que scrollWidth refleje el ancho real
                    // del contenido, no el heredado del contenedor.
                    const prevTableMinWidth = table?.style.minWidth ?? '';
                    table?.style.setProperty('min-width', '0px');
                    const fullWidth = table ? table.scrollWidth : (scrollBox ? scrollBox.scrollWidth : el.scrollWidth);
                    table?.style.setProperty('min-width', prevTableMinWidth || '');
                    const nodosAAjustar = [el, wrapper, scrollBox].filter(Boolean);
                    const prevWidths = nodosAAjustar.map((n) => n.style.width);
                    nodosAAjustar.forEach((n) => n.style.setProperty('width', fullWidth + 'px'));
                    try {
                        const canvas = await window.html2canvas(el, {
                            backgroundColor: '#ffffff',
                            scale: 2,
                            windowWidth: fullWidth,
                            width: fullWidth,
                            onclone: (clonedDoc) => {
                                const area = clonedDoc.getElementById('areaExportable');
                                const cloneWrapper = area?.querySelector('.compact-table-wrapper');
                                const box = area?.querySelector('.table-scroll-container, .scroll-container-visible');
                                box?.style.setProperty('overflow', 'visible');
                                box?.style.setProperty('max-height', 'none');
                                area?.classList.add('exporting-compact');
                                [area, cloneWrapper, box].forEach((n) => n?.style.setProperty('width', fullWidth + 'px'));

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
                                this.mostrarMensaje('No se pudo generar la imagen (canvas vacío).', 'error');
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
                        this.mostrarMensaje('No se pudo generar la imagen: ' + (err?.message || err), 'error');
                        console.error('exportarComoImagen: html2canvas lanzó un error:', err);
                    } finally {
                        el.classList.remove('exporting-compact');
                        nodosAAjustar.forEach((n, i) => { n.style.width = prevWidths[i]; });
                        scrollBox?.style.setProperty('overflow', prevOverflow);
                        scrollBox?.style.setProperty('max-height', prevMaxHeight);
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
                        this.mostrarMensaje('No se pudo copiar automáticamente: la imagen se descargó.', 'warning');
                    }
                },
                // Mismo toast que el resto de /personal/* (pedido
                // 2026-09-28: todas las notificaciones iguales) — antes era
                // una burbuja oscura propia en cada vista.
                mostrarMensaje(texto, tipo = 'success') {
                    showCrudToast(texto, tipo);
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

        // Confirmacion con el mismo estilo del panel (pedido 2026-09-28:
        // "toda notificacion correcta") en vez del confirm() nativo.
        // Devuelve una promesa: true si confirma, false si cancela (boton,
        // Escape o clic fuera). Uso: if (!(await confirmarAccion({...}))) return;
        function confirmarAccion({ titulo = '¿Confirmar acción?', mensaje = '', textoConfirmar = 'Confirmar' } = {}) {
            const dialogo = document.getElementById('confirmDialog');
            if (!dialogo) {
                return Promise.resolve(window.confirm(mensaje || titulo));
            }

            const ok = document.getElementById('confirmDialogOk');
            const cancelar = document.getElementById('confirmDialogCancel');
            document.getElementById('confirmDialogTitle').textContent = titulo;
            document.getElementById('confirmDialogMessage').textContent = mensaje;
            ok.textContent = textoConfirmar;
            dialogo.style.display = 'flex';
            ok.focus();

            return new Promise((resolve) => {
                const cerrar = (resultado) => {
                    dialogo.style.display = 'none';
                    ok.removeEventListener('click', alConfirmar);
                    cancelar.removeEventListener('click', alCancelar);
                    dialogo.removeEventListener('click', alClicFuera);
                    document.removeEventListener('keydown', alTeclado, true);
                    resolve(resultado);
                };
                const alConfirmar = () => cerrar(true);
                const alCancelar = () => cerrar(false);
                const alClicFuera = (e) => { if (e.target === dialogo) cerrar(false); };
                const alTeclado = (e) => {
                    if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); cerrar(false); }
                };
                ok.addEventListener('click', alConfirmar);
                cancelar.addEventListener('click', alCancelar);
                dialogo.addEventListener('click', alClicFuera);
                document.addEventListener('keydown', alTeclado, true);
            });
        }

        {{-- Mensajes de exito por redirect (ej. BitacoraController::cuota())
             usaban un cartel fijo arriba del contenido que quedaba plantado
             hasta la siguiente navegacion — se reemplaza por el mismo toast
             flotante de arriba para que todo /personal/* notifique igual. --}}
        @if (session('success'))
            showCrudToast(@js(session('success')));
        @endif

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
