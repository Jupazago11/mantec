<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidencia del reporte</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen p-4 md:p-6">
        <div class="mx-auto max-w-7xl space-y-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Evidencia del reporte</h2>
                    <p class="mt-2 text-slate-600">
                        Visualización de imágenes y videos asociados a este reporte.
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
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recomendación</div>
                                <div class="mt-1 rounded-xl bg-slate-50 px-4 py-3 text-slate-700">
                                    {!! (($report->recommendation ?? null) && trim((string) $report->recommendation) !== '')
                                        ? nl2br(e(ltrim((string) $report->recommendation)))
                                        : '—' !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    @php
                        $mediaFiles = $report->files->values();
                        $imageFiles = $report->files->where('file_type', 'image')->values();

                        $galleryImages = $imageFiles->map(function ($file) {
                            return [
                                'src' => route('admin.report-evidence.open', $file),
                                'alt' => $file->original_name,
                            ];
                        })->values()->toArray();
                    @endphp

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Evidencia multimedia</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Total: {{ $mediaFiles->count() }}
                            </p>
                        </div>

                        <div class="mt-6">
                            @if($mediaFiles->count() > 0)
                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach($mediaFiles as $file)
                                        @php
                                            $openUrl = route('admin.report-evidence.open', $file);
                                        @endphp

                                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                            @if($file->file_type === 'video')
                                                <video controls class="h-56 w-full bg-black object-cover">
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
                                                    onclick="openImageModal({{ $imageIndex }})"
                                                    class="block w-full"
                                                    title="Ver imagen completa"
                                                >
                                                    <img
                                                        src="{{ $openUrl }}"
                                                        alt="{{ $file->original_name }}"
                                                        class="h-56 w-full object-cover transition hover:opacity-90"
                                                    >
                                                </button>
                                            @endif

                                            <div class="space-y-2 p-4">
                                                <div class="text-sm font-medium text-slate-900 break-all">
                                                    {{ $file->original_name }}
                                                </div>

                                                <div class="text-xs uppercase tracking-wide text-slate-500">
                                                    {{ $file->file_type === 'video' ? 'Video' : 'Imagen' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                    Este reporte no tiene evidencias registradas.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal imagen --}}
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

    <script>
        const imageModal = document.getElementById('imageModal');
        const imageModalImg = document.getElementById('imageModalImg');
        const galleryImages = @json($galleryImages);

        let currentImageIndex = 0;

        function renderCurrentImage() {
            if (!galleryImages.length) return;

            const current = galleryImages[currentImageIndex];
            imageModalImg.src = current.src;
            imageModalImg.alt = current.alt ?? '';
        }

        function openImageModal(index = 0) {
            if (!galleryImages.length) return;

            currentImageIndex = index >= 0 ? index : 0;
            renderCurrentImage();

            imageModal.classList.remove('hidden');
            imageModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeImageModal() {
            imageModal.classList.remove('flex');
            imageModal.classList.add('hidden');
            imageModalImg.src = '';
            imageModalImg.alt = '';
            document.body.classList.remove('overflow-hidden');
        }

        function showPrevImage() {
            if (!galleryImages.length) return;

            currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
            renderCurrentImage();
        }

        function showNextImage() {
            if (!galleryImages.length) return;

            currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
            renderCurrentImage();
        }

        function closeEvidenceTab() {
            window.close();
        }

        document.addEventListener('keydown', function (event) {
            if (imageModal.classList.contains('hidden')) return;

            if (event.key === 'Escape') {
                closeImageModal();
            }

            if (event.key === 'ArrowLeft') {
                showPrevImage();
            }

            if (event.key === 'ArrowRight') {
                showNextImage();
            }
        });
    </script>
</body>
</html>
