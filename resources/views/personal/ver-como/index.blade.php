<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver como {{ $empleado->nickname }} · ManTec</title>
    @vite(['resources/css/app.css'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
{{--
    "Ver como supervisor" (pedido 2026-09-19, seccion 14.18) — pantalla
    minima, pensada como celular, para prototipar la logica del API que
    consumira la futura app Android (seccion 7 del documento, repo aparte).
    A proposito NO extiende layouts.personal: no debe tener el sidebar/
    topbar del panel admin, solo lo esencial. Se abre en pestaña nueva sin
    tocar la sesion de superadmin (ver SupervisorViewController).
--}}
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div
        x-data="verComoPage({
            actividades: @js($actividadesJs),
            saveUrlTemplate: @js($saveUrlTemplate),
            evidenciasUrlTemplate: @js($evidenciasUrlTemplate),
            csrfToken: @js(csrf_token()),
            hoy: @js($fecha),
        })"
        class="mx-auto max-w-md space-y-4 p-4 pb-16"
    >
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <p class="font-semibold">Prototipo — vista de supervisor</p>
            <p class="mt-0.5">Viendo como <span class="font-semibold">{{ $empleado->nombre }}</span> ({{ $empleado->nickname }}) — {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') }}. Tu sesión de superadmin no cambió.</p>
        </div>

        <h1 class="text-lg font-bold text-slate-900">Actividades de hoy</h1>

        <template x-if="actividades.length === 0">
            <div class="flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i data-lucide="calendar-check" class="h-6 w-6"></i>
                </span>
                <p class="text-sm font-semibold text-slate-700">Sin actividades por diligenciar</p>
                <p class="max-w-xs text-xs text-slate-400">{{ $empleado->nickname }} no tiene actividades programadas para hoy, ni turnos nocturnos pendientes de ayer.</p>
            </div>
        </template>

        <template x-for="a in actividades" :key="a.id">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <button type="button" @click="toggle(a)" class="flex w-full items-start justify-between gap-3 p-4 text-left">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-bold"
                                :class="a.activity_type === 'P' ? 'bg-[#d55b20] text-white' : 'bg-slate-300 text-slate-700'"
                                x-text="a.activity_type"
                            ></span>
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="a.description"></p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500" x-text="[a.company_name, a.team, a.process].filter(Boolean).join(' · ')"></p>
                        <p class="mt-0.5 text-xs text-slate-400" x-text="(a.estimated_hours ?? '—') + 'h programadas · ' + a.shift"></p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            :class="a.registrado ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                            x-text="a.registrado ? 'Registrado' : 'Pendiente'"
                        ></span>
                        <span
                            x-show="a.date !== hoy"
                            x-cloak
                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800"
                        >Turno de ayer</span>
                    </div>
                </button>

                <div x-show="abierta === a.id" x-cloak x-transition class="space-y-4 border-t border-slate-100 p-4">
                    <template x-if="a.closed">
                        <p class="rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-500">
                            Esta actividad ya fue cerrada en Diario de Campo — no se puede editar el registro.
                        </p>
                    </template>

                    <template x-if="!a.closed">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Comentarios</label>
                                <textarea
                                    x-model="form(a).comments"
                                    rows="3"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    placeholder="Novedades de la actividad ejecutada..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">¿Todos trabajaron las horas programadas?</label>
                                <div class="inline-flex rounded-lg border border-slate-300 p-0.5">
                                    <button
                                        type="button"
                                        @click="form(a).all_worked_scheduled_hours = true"
                                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                        :class="form(a).all_worked_scheduled_hours ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                                    >Sí</button>
                                    <button
                                        type="button"
                                        @click="form(a).all_worked_scheduled_hours = false"
                                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                                        :class="form(a).all_worked_scheduled_hours === false ? 'bg-[#d55b20] text-white' : 'text-slate-600 hover:bg-slate-50'"
                                    >No</button>
                                </div>
                            </div>

                            {{-- Detalle por persona (simplificado 2026-09-19: sin hora
                                 inicio/hora final por ahora — un solo campo de horas,
                                 precargado con lo programado, que el supervisor ajusta
                                 solo para quien no trabajó las horas acordadas). --}}
                            <div x-show="form(a).all_worked_scheduled_hours === false" x-cloak class="space-y-2">
                                <p class="text-xs font-medium text-slate-600">Horas trabajadas por persona</p>
                                <template x-for="p in a.personas" :key="p.id">
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                                        <span class="text-sm font-medium text-slate-700" x-text="p.nombre"></span>
                                        <div class="flex items-center gap-1.5">
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                x-model.number="personaForm(a, p.id).worked_hours"
                                                class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm"
                                            >
                                            <span class="text-xs text-slate-400">h</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Evidencias (seccion 14.20): endpoint propio
                                 (ActivityEvidenceController), independiente
                                 del guardado de comentarios/horas de abajo. --}}
                            <div class="space-y-2 border-t border-slate-100 pt-4">
                                <p class="text-xs font-medium text-slate-600">Evidencias (fotos / video)</p>

                                <div class="grid grid-cols-3 gap-2" x-show="a.evidencias.length > 0">
                                    <template x-for="ev in a.evidencias" :key="ev.id">
                                        <div class="group relative aspect-square overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                            <a :href="ev.open_url" target="_blank" rel="noopener" class="relative flex h-full w-full items-center justify-center" :title="ev.original_name">
                                                {{-- Icono de respaldo detras: si es video, o si la
                                                     miniatura de imagen falla al cargar (URL firmada
                                                     vencida, archivo eliminado en R2, etc.). --}}
                                                <i :data-lucide="ev.file_type === 'video' ? 'video' : 'image'" class="h-6 w-6 text-slate-400"></i>
                                                <template x-if="ev.file_type === 'image'">
                                                    <img
                                                        :src="ev.open_url"
                                                        :alt="ev.original_name"
                                                        loading="lazy"
                                                        class="absolute inset-0 h-full w-full object-cover"
                                                        @@error="$el.remove()"
                                                    >
                                                </template>
                                            </a>
                                            <button
                                                type="button"
                                                @click="borrarEvidencia(a, ev.id)"
                                                class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-slate-900/70 text-white"
                                                title="Eliminar evidencia"
                                            ><i data-lucide="x" class="h-3 w-3"></i></button>
                                        </div>
                                    </template>
                                </div>

                                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 px-3 py-2.5 text-xs font-medium text-slate-500 hover:bg-slate-50">
                                    <i data-lucide="upload" class="h-4 w-4"></i>
                                    <span x-text="subiendo === a.id ? 'Subiendo...' : 'Agregar foto o video'"></span>
                                    <input type="file" accept="image/*,video/*" multiple class="hidden" :disabled="subiendo === a.id" @change="subirEvidencias(a, $event)">
                                </label>
                            </div>

                            <button
                                type="button"
                                @click="guardar(a)"
                                :disabled="guardando"
                                class="w-full rounded-xl bg-[#d55b20] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b8481a] disabled:cursor-not-allowed disabled:opacity-60"
                                x-text="guardando ? 'Guardando...' : 'Guardar registro'"
                            ></button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div
        id="toast"
        class="fixed bottom-4 left-1/2 z-[100000] hidden w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-2xl border px-4 py-3 text-sm shadow-2xl"
        role="status"
        aria-live="polite"
    ></div>

    <script>
        let toastTimeout = null;
        function mostrarToast(mensaje, tipo = 'success') {
            const el = document.getElementById('toast');
            el.className = 'fixed bottom-4 left-1/2 z-[100000] w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-2xl border px-4 py-3 text-sm shadow-2xl ' +
                (tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800');
            el.textContent = mensaje;
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => { el.classList.add('hidden'); }, tipo === 'error' ? 6000 : 3000);
        }

        function verComoPage({ actividades, saveUrlTemplate, evidenciasUrlTemplate, csrfToken, hoy }) {
            return {
                actividades,
                hoy,
                abierta: null,
                formularios: {},
                guardando: false,
                subiendo: null,
                toggle(a) {
                    this.abierta = this.abierta === a.id ? null : a.id;
                    this.$nextTick(() => window.lucide?.createIcons());
                },
                // Estado de edicion por actividad — se crea perezosamente la
                // primera vez que se abre, sembrado con lo que ya venia
                // guardado (para poder reabrir y corregir).
                form(a) {
                    if (!this.formularios[a.id]) {
                        this.formularios[a.id] = {
                            comments: a.comments ?? '',
                            all_worked_scheduled_hours: a.all_worked_scheduled_hours ?? true,
                            personas: {},
                        };
                    }
                    return this.formularios[a.id];
                },
                // Precargado con la hora programada de la actividad
                // (pedido 2026-09-19: "en automático tendrá la hora
                // programada y desde allí podrá modificarla") — el
                // supervisor solo la ajusta para quien no trabajó las
                // horas acordadas.
                personaForm(a, personaId) {
                    const f = this.form(a);
                    if (!f.personas[personaId]) {
                        const p = a.personas.find((x) => x.id === personaId);
                        f.personas[personaId] = {
                            worked_hours: p?.worked_hours ?? a.estimated_hours ?? 0,
                        };
                    }
                    return f.personas[personaId];
                },
                async guardar(a) {
                    this.guardando = true;
                    try {
                        const f = this.form(a);
                        const payload = {
                            comments: f.comments || null,
                            all_worked_scheduled_hours: f.all_worked_scheduled_hours,
                            personas: f.all_worked_scheduled_hours
                                ? []
                                : a.personas.map((p) => ({
                                    employee_id: p.id,
                                    worked_hours: this.personaForm(a, p.id).worked_hours,
                                })),
                        };

                        const res = await fetch(saveUrlTemplate.replace('__ID__', a.id), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json().catch(() => null);

                        if (res.status === 422 && data?.errors) {
                            mostrarToast(Object.values(data.errors).flat().join(' '), 'error');
                            return;
                        }
                        if (!res.ok || !data?.success) {
                            mostrarToast(data?.message || 'No se pudo guardar el registro.', 'error');
                            return;
                        }

                        const idx = this.actividades.findIndex((x) => x.id === data.activity.id);
                        if (idx !== -1) this.actividades[idx] = data.activity;
                        delete this.formularios[a.id];
                        this.abierta = null;
                        mostrarToast(data.message || 'Registro guardado correctamente.', 'success');
                    } catch (e) {
                        console.error('guardar (ver-como):', e);
                        mostrarToast('No se pudo guardar el registro (error de red).', 'error');
                    } finally {
                        this.guardando = false;
                    }
                },
                async subirEvidencias(a, event) {
                    const files = Array.from(event.target.files || []);
                    if (files.length === 0) return;

                    this.subiendo = a.id;
                    const formData = new FormData();
                    files.forEach((file) => formData.append('files[]', file));

                    try {
                        const res = await fetch(evidenciasUrlTemplate.replace('__ID__', a.id), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        });
                        const data = await res.json().catch(() => null);

                        if (res.status === 422 && data?.errors) {
                            mostrarToast(Object.values(data.errors).flat().join(' '), 'error');
                            return;
                        }
                        if (!res.ok || !data?.success) {
                            mostrarToast(data?.message || 'No se pudo subir la evidencia.', 'error');
                            return;
                        }

                        a.evidencias.push(...data.evidencias);
                        mostrarToast(data.message || 'Evidencia cargada correctamente.', 'success');
                        this.$nextTick(() => window.lucide?.createIcons());
                    } catch (e) {
                        console.error('subirEvidencias (ver-como):', e);
                        mostrarToast('No se pudo subir la evidencia (error de red).', 'error');
                    } finally {
                        this.subiendo = null;
                        event.target.value = '';
                    }
                },
                async borrarEvidencia(a, evidenceId) {
                    const evidencia = a.evidencias.find((ev) => ev.id === evidenceId);
                    if (!evidencia) return;

                    try {
                        const res = await fetch(evidencia.delete_url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });
                        const data = await res.json().catch(() => null);

                        if (!res.ok || !data?.success) {
                            mostrarToast(data?.message || 'No se pudo eliminar la evidencia.', 'error');
                            return;
                        }

                        a.evidencias = a.evidencias.filter((ev) => ev.id !== evidenceId);
                        mostrarToast(data.message || 'Evidencia eliminada.', 'success');
                    } catch (e) {
                        console.error('borrarEvidencia (ver-como):', e);
                        mostrarToast('No se pudo eliminar la evidencia (error de red).', 'error');
                    }
                },
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); });
        document.addEventListener('alpine:init', () => { if (window.lucide) window.lucide.createIcons(); });
    </script>
</body>
</html>
