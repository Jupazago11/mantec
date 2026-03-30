@props([
    'title' => 'Título',
    'value' => '0',
    'description' => null,
])

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>

    @if($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif
</div>