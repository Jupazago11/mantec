<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidencia del reporte</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $canManageEvidence = in_array(auth()->user()?->role?->key, ['superadmin', 'admin_global', 'admin'], true);

        $evidenceSections = [
            [
                'key' => \App\Models\ReportDetailFile::KIND_HALLAZGO,
                'title' => 'Evidencia de hallazgo',
                'description' => '',
                'empty' => 'Este reporte no tiene evidencia de hallazgo registrada.',
                'files' => $report->files
                    ->where('evidence_kind', \App\Models\ReportDetailFile::KIND_HALLAZGO)
                    ->values(),
            ],
            [
                'key' => \App\Models\ReportDetailFile::KIND_CORRECCION,
                'title' => 'Evidencia de corrección',
                'description' => '',
                'empty' => 'Este reporte no tiene evidencia de corrección registrada.',
                'files' => $report->files
                    ->where('evidence_kind', \App\Models\ReportDetailFile::KIND_CORRECCION)
                    ->values(),
            ],
        ];

        $galleryImagesByKind = collect($evidenceSections)
            ->mapWithKeys(function ($section) {
                return [
                    $section['key'] => $section['files']
                        ->where('file_type', 'image')
                        ->map(function ($file) {
                            return [
                                'src' => route('admin.report-evidence.open', $file),
                                'alt' => $file->original_name,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    @endphp

    <div class="min-h-screen p-4 md:p-6">
        <div class="mx-auto max-w-7xl space-y-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Evidencia del reporte</h2>
                    <p class="mt-2 text-slate-600">
                        Visualización y carga de imágenes o videos asociados a este reporte.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeEvidenceTab()"
                    class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                >
                    Cerrar pestaña
                </button>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-1">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900">Información del reporte</h3>

                        <div class="mt-5 space-y-4 text-sm">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</div>
                                <div class="mt-1 text-slate-900">{{ $report->element?->area?->client?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Área</div>
                                <div class="mt-1 text-slate-900">{{ $report->element?->area?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Activo</div>
                                <div class="mt-1 text-slate-900">{{ $report->element?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de activo</div>
                                <div class="mt-1 text-slate-900">{{ $report->element?->elementType?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Componente</div>
                                <div class="mt-1 text-slate-900">{{ $report->component?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diagnóstico</div>
                                <div class="mt-1 text-slate-900">{{ $report->diagnostic?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Condición</div>
                                <div class="mt-2">
                                    @if($report->condition)
                                        <span
                                            class="inline-flex rounded-lg px-3 py-1 font-medium"
                                            style="background-color: {{ $report->condition->color ?? '#e2e8f0' }}; color: #0f172a;"
                                        >
                                            {{ $report->condition->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-900">—</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inspector</div>
                                <div class="mt-1 text-slate-900">{{ $report->user?->name ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de reporte</div>
                                <div class="mt-1 text-slate-900">
                                    {{ $report->created_at ? $report->created_at->format('Y-m-d H:i') : '—' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hallazgo</div>
                                <div class="mt-1 rounded-xl bg-slate-50 px-4 py-3 text-slate-700 break-words whitespace-normal">
                                    {!! (($report->recommendation ?? null) && trim((string) $report->recommendation) !== '')
                                        ? nl2br(e(ltrim((string) $report->recommendation)))
                                        : '—' !!}
                                </div>
                            </div>

                            @if(($report->recommendation_2 ?? null) && trim((string) $report->recommendation_2) !== '')
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recomendación</div>
                                    <div class="mt-1 rounded-xl bg-slate-50 px-4 py-3 text-slate-700 break-words whitespace-normal">
                                        {!! nl2br(e(ltrim((string) $report->recommendation_2))) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4 xl:col-span-2">
                    @foreach($evidenceSections as $section)
                        @php
                            $imageFiles = $section['files']->where('file_type', 'image')->values();
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-900">{{ $section['title'] }}</h3>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                            {{ $section['files']->count() }} archivo{{ $section['files']->count() === 1 ? '' : 's' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $section['description'] }}</p>
                                </div>

                                @if($canManageEvidence)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.preventive-reports.evidence.store', $report) }}"
                                        enctype="multipart/form-data"
                                        class="flex w-full justify-start lg:w-auto lg:justify-end"
                                    >
                                        @csrf
                                        <input type="hidden" name="evidence_kind" value="{{ $section['key'] }}">
                                        <input
                                            id="evidence-upload-{{ $section['key'] }}"
                                            type="file"
                                            name="files[]"
                                            accept="image/*,video/*"
                                            multiple
                                            class="hidden"
                                            onchange="if (this.files.length) this.form.submit()"
                                        >
                                        <button
                                            type="button"
                                            onclick="document.getElementById('evidence-upload-{{ $section['key'] }}').click()"
                                            class="inline-flex items-center justify-center rounded-lg bg-[#d94d33] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#b83f29]"
                                        >
                                            Subir
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="mt-4">
                                @if($section['files']->count() > 0)
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                                        @foreach($section['files'] as $file)
                                            @php
                                                $openUrl = route('admin.report-evidence.open', $file);
                                            @endphp

                                            <div class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                                                <div class="relative w-full overflow-hidden bg-slate-100" style="padding-top: 75%;">
                                                    @if($canManageEvidence)
                                                        <button
                                                            type="button"
                                                            onclick="openDetachModal('{{ route('admin.report-evidence.destroy', $file) }}', @js($file->original_name))"
                                                            class="absolute right-2 top-2 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-red-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                                            title="Eliminar evidencia"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6"/>
                                                            </svg>
                                                        </button>
                                                    @endif

                                                    @if($file->file_type === 'video')
                                                        <video controls class="absolute inset-0 h-full w-full bg-black object-cover">
                                                            <source src="{{ $openUrl }}" type="{{ $file->mime_type }}">
                                                            Tu navegador no soporta reproducción de video.
                                                        </video>
                                                    @else
                                                        @php
                                                            $imageIndex = $imageFiles->search(function ($img) use ($file) {
                                                                return $img->id === $file->id;
                                                            });
                                                        @endphp

                                                        <button
                                                            type="button"
                                                            onclick="openImageModal('{{ $section['key'] }}', {{ $imageIndex }})"
                                                            class="absolute inset-0 block h-full w-full"
                                                            title="Ver imagen completa"
                                                        >
                                                            <img
                                                                src="{{ $openUrl }}"
                                                                alt="{{ $file->original_name }}"
                                                                class="h-full w-full object-cover transition hover:opacity-90"
                                                            >
                                                        </button>
                                                    @endif
                                                </div>

                                                <div class="flex flex-1 flex-col gap-3 p-3">
                                                    <div
                                                        class="min-h-[2.75rem] overflow-hidden text-sm font-medium leading-5 text-slate-900 break-all"
                                                        style="display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2;"
                                                    >
                                                        {{ $file->original_name }}
                                                    </div>

                                                    <div class="flex items-center justify-between gap-3 text-[11px] uppercase tracking-wide text-slate-500">
                                                        <span>{{ $file->file_type === 'video' ? 'Video' : 'Imagen' }}</span>
                                                        <span>{{ $file->created_at?->format('Y-m-d H:i') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                        <div>{{ $section['empty'] }}</div>
                                        @if($canManageEvidence)
                                            <div class="mt-1 text-[11px] text-slate-400">
                                                Puedes cargar archivos desde el panel superior de este bloque.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div id="evidenceToastContainer" class="fixed bottom-5 right-5 z-[99999] space-y-3"></div>

    <div
        id="imageModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90"
    >
        <button
            type="button"
            onclick="closeImageModal()"
            class="absolute right-4 top-4 z-20 rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white shadow hover:bg-[#b83f29]"
        >
            Cerrar imagen
        </button>

        <button
            type="button"
            onclick="showPrevImage()"
            class="absolute left-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-3 text-slate-900 shadow transition hover:bg-white"
            aria-label="Imagen anterior"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14.78 5.22a.75.75 0 0 1 0 1.06L9.06 12l5.72 5.72a.75.75 0 1 1-1.06 1.06l-6.25-6.25a.75.75 0 0 1 0-1.06l6.25-6.25a.75.75 0 0 1 1.06 0Z"/>
            </svg>
        </button>

        <img
            id="imageModalImg"
            src=""
            alt=""
            class="max-h-[95vh] max-w-[95vw] object-contain"
        >

        <button
            type="button"
            onclick="showNextImage()"
            class="absolute right-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-3 text-slate-900 shadow transition hover:bg-white"
            aria-label="Imagen siguiente"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9.22 18.78a.75.75 0 0 1 0-1.06L14.94 12 9.22 6.28a.75.75 0 1 1 1.06-1.06l6.25 6.25a.75.75 0 0 1 0 1.06l-6.25 6.25a.75.75 0 0 1-1.06 0Z"/>
            </svg>
        </button>
    </div>

    @if($canManageEvidence)
        <div
            id="detachModal"
            class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/60 px-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-slate-900">Eliminar evidencia</h3>
                <p class="mt-2 text-sm text-slate-600">
                    ¿Está seguro de eliminar esta evidencia?
                </p>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 break-all">
                    <span id="detachModalFileName">Archivo</span>
                </div>

                <form id="detachModalForm" method="POST" class="mt-5 flex items-center justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button
                        type="button"
                        onclick="closeDetachModal()"
                        class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-[#d94d33] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b83f29]"
                    >
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    @endif

    <script>
        const evidenceToastContainer = document.getElementById('evidenceToastContainer');
        const imageModal = document.getElementById('imageModal');
        const imageModalImg = document.getElementById('imageModalImg');
        const galleryImagesByKind = @json($galleryImagesByKind);
        const detachModal = document.getElementById('detachModal');
        const detachModalForm = document.getElementById('detachModalForm');
        const detachModalFileName = document.getElementById('detachModalFileName');
        const evidenceSuccessMessage = @json(session('success'));
        const evidenceErrorMessages = @json($errors->all());

        let currentImageKind = null;
        let currentImageIndex = 0;

        function showEvidenceToast(message, type = 'success') {
            if (!message || !evidenceToastContainer) {
                return;
            }

            const toast = document.createElement('div');
            const styles = type === 'error'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700';

            toast.className = `w-[340px] max-w-[calc(100vw-2rem)] rounded-2xl border px-4 py-3 text-sm font-semibold shadow-2xl ${styles}`;
            toast.textContent = message;

            evidenceToastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2', 'transition', 'duration-300');
                setTimeout(() => toast.remove(), 350);
            }, 3500);
        }

        function currentGallery() {
            return currentImageKind ? (galleryImagesByKind[currentImageKind] || []) : [];
        }

        function renderCurrentImage() {
            const gallery = currentGallery();

            if (!gallery.length) {
                return;
            }

            const current = gallery[currentImageIndex];
            imageModalImg.src = current.src;
            imageModalImg.alt = current.alt ?? '';
        }

        function openImageModal(kind, index) {
            const gallery = galleryImagesByKind[kind] || [];

            if (!gallery.length) {
                return;
            }

            currentImageKind = kind;
            currentImageIndex = index;
            renderCurrentImage();
            imageModal.classList.remove('hidden');
            imageModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeImageModal() {
            imageModal.classList.add('hidden');
            imageModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            imageModalImg.src = '';
            imageModalImg.alt = '';
        }

        function showPrevImage() {
            const gallery = currentGallery();

            if (!gallery.length) {
                return;
            }

            currentImageIndex = (currentImageIndex - 1 + gallery.length) % gallery.length;
            renderCurrentImage();
        }

        function showNextImage() {
            const gallery = currentGallery();

            if (!gallery.length) {
                return;
            }

            currentImageIndex = (currentImageIndex + 1) % gallery.length;
            renderCurrentImage();
        }

        function closeEvidenceTab() {
            if (window.history.length > 1) {
                window.close();
                window.history.back();
                return;
            }

            window.location.href = document.referrer || '/admin/dashboard';
        }

        function openDetachModal(action, fileName) {
            if (!detachModal || !detachModalForm || !detachModalFileName) {
                return;
            }

            detachModalForm.action = action;
            detachModalFileName.textContent = fileName || 'Archivo';
            detachModal.classList.remove('hidden');
            detachModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeDetachModal() {
            if (!detachModal) {
                return;
            }

            detachModal.classList.add('hidden');
            detachModal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', (event) => {
            if (imageModal.classList.contains('hidden')) {
                if (event.key === 'Escape') {
                    closeDetachModal();
                }
            } else {
                if (event.key === 'Escape') {
                    closeImageModal();
                }

                if (event.key === 'ArrowLeft') {
                    showPrevImage();
                }

                if (event.key === 'ArrowRight') {
                    showNextImage();
                }
            }
        });

        imageModal.addEventListener('click', (event) => {
            if (event.target === imageModal) {
                closeImageModal();
            }
        });

        if (detachModal) {
            detachModal.addEventListener('click', (event) => {
                if (event.target === detachModal) {
                    closeDetachModal();
                }
            });
        }

        if (evidenceSuccessMessage) {
            showEvidenceToast(evidenceSuccessMessage, 'success');
        }

        if (Array.isArray(evidenceErrorMessages) && evidenceErrorMessages.length) {
            evidenceErrorMessages.forEach((message) => showEvidenceToast(message, 'error'));
        }
    </script>
</body>
</html>
