<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reportes preventivos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen p-6">
        <div class="mx-auto max-w-[1900px] space-y-6">

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporte preventivo {{ $elementType->name }} Planta {{ $client->name }} {{ $currentYear }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Registros del año actual ordenados por semana descendente.
                </p>
            </div>

            <form method="GET" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Nombre del activo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    ID almacén
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Diagnóstico
                                </th>
                                <th class="min-w-[520px] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Recomendación
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Orden
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Aviso
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Responsable
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    Fecha de<br>reporte
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    Fecha de<br>ejecución
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    Condición del<br>activo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 leading-4">
                                    Ejecución<br>orden
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Semana
                                </th>
                            </tr>

                            <tr class="border-t border-slate-200 bg-white">
                                <th class="px-6 py-3">
                                    <input type="text" name="element_name" value="{{ request('element_name') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3"></th>
                                <th class="px-6 py-3">
                                    <input type="text" name="diagnostic_name" value="{{ request('diagnostic_name') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="text" name="recommendation" value="{{ request('recommendation') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="text" name="orden" value="{{ request('orden') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="text" name="aviso" value="{{ request('aviso') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="text" name="responsable" value="{{ request('responsable') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="date" name="report_date" value="{{ request('report_date') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="date" name="execution_date" value="{{ request('execution_date') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                </th>
                                <th class="px-6 py-3">
                                    <input type="text" name="condition_name" value="{{ request('condition_name') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Filtrar">
                                </th>
                                <th class="px-6 py-3">
                                    <select name="execution_status"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                        <option value="">Todos</option>
                                        <option value="pendiente" @selected(request('execution_status') === 'pendiente')>Pendiente</option>
                                        <option value="realizado" @selected(request('execution_status') === 'realizado')>Realizado</option>
                                    </select>
                                </th>
                                <th class="px-6 py-3">
                                    <input type="number" name="week" value="{{ request('week') }}"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                        placeholder="Semana" min="1" max="53">
                                </th>
                            </tr>

                            <tr class="border-t border-slate-200 bg-white">
                                <th colspan="12" class="px-6 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url()->current() }}"
                                           class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                            Limpiar
                                        </a>
                                        <button type="submit"
                                            class="rounded-lg bg-[#d94d33] px-4 py-2 text-xs font-semibold text-white hover:bg-[#b83f29]">
                                            Filtrar
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($reports as $report)
                                @php
                                    $isDone = ($report->executionStatus?->name ?? null) === 'Realizado';
                                @endphp

                                <tr class="align-top hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                        {{ $report->element?->name ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        —
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        {{ $report->diagnostic?->name ?? '—' }}
                                    </td>

                                    <td class="min-w-[520px] px-6 py-4 text-sm leading-6 text-slate-700 whitespace-pre-line">
                                        {{ $report->recommendation ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        {{ $report->orden ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        {{ $report->aviso ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                        {{ $report->user?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        @if($report->created_at)
                                            <div>{{ $report->created_at->format('Y-m-d') }}</div>
                                            <div class="text-xs text-slate-500">{{ $report->created_at->format('H:i') }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700" id="execution-date-{{ $report->id }}">
                                        {{ $report->execution_date ?: '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ $report->condition?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        <label
                                            id="execution-badge-{{ $report->id }}"
                                            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold {{ $isDone ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800' }}"
                                        >
                                            <input
                                                type="checkbox"
                                                class="execution-checkbox rounded border-slate-300 text-[#d94d33] focus:ring-[#d94d33]"
                                                data-id="{{ $report->id }}"
                                                {{ $isDone ? 'checked' : '' }}
                                            >
                                            <span>{{ $isDone ? 'Realizado' : 'Pendiente' }}</span>
                                        </label>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">
                                        {{ $report->week }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No hay reportes para este tipo de activo en el año actual.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reports->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $reports->links() }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function toggleExecution(checkbox) {
            const reportId = checkbox.dataset.id;
            const isChecked = checkbox.checked;

            const badge = document.getElementById(`execution-badge-${reportId}`);
            const dateCell = document.getElementById(`execution-date-${reportId}`);

            try {
                const response = await fetch(`/admin/preventive-reports/report-details/${reportId}/toggle-execution`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        is_checked: isChecked ? 1 : 0
                    })
                });

                if (!response.ok) {
                    throw new Error('No fue posible actualizar el estado.');
                }

                const data = await response.json();

                if (isChecked) {
                    badge.classList.remove('bg-amber-100', 'text-amber-800');
                    badge.classList.add('bg-green-100', 'text-green-700');
                    badge.querySelector('span').textContent = 'Realizado';
                    dateCell.textContent = data.execution_date ?? '—';
                } else {
                    badge.classList.remove('bg-green-100', 'text-green-700');
                    badge.classList.add('bg-amber-100', 'text-amber-800');
                    badge.querySelector('span').textContent = 'Pendiente';
                    dateCell.textContent = '—';
                }
            } catch (error) {
                checkbox.checked = !isChecked;
                alert(error.message);
            }
        }

        document.querySelectorAll('.execution-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                toggleExecution(this);
            });
        });
    </script>
</body>
</html>