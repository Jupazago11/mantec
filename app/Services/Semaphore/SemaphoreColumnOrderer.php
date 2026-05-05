<?php

namespace App\Services\Semaphore;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SemaphoreColumnOrderer
{
    public function orderCollection(Collection $columns): Collection
    {
        $ordered = $columns->values();

        foreach ($ordered->values() as $column) {
            if (($column->column_type ?? null) !== 'belt_change_manual') {
                continue;
            }

            $sourceKey = trim((string) ($column->source_column_key ?? ''));

            if ($sourceKey === '') {
                continue;
            }

            $currentIndex = $ordered->search(fn ($item) => $item->id === $column->id);
            $sourceIndex = $ordered->search(fn ($item) => $item->key === $sourceKey);

            if ($currentIndex === false || $sourceIndex === false || $currentIndex < $sourceIndex) {
                continue;
            }

            $item = $ordered->pull($currentIndex);
            $sourceIndex = $ordered->search(fn ($entry) => $entry->key === $sourceKey);

            if ($sourceIndex === false) {
                $ordered->push($item);
                continue;
            }

            $ordered = $ordered->splice(0, $sourceIndex)
                ->push($item)
                ->merge($ordered);
        }

        return $ordered->values();
    }

    public function normalizePayload(array $columns): array
    {
        $normalized = array_values($columns);

        foreach ($normalized as $index => $column) {
            $computedKey = Str::slug(trim((string) ($column['key'] ?? '')))
                ?: Str::slug(trim((string) ($column['label'] ?? '')))
                ?: 'column-' . ($index + 1);

            $normalized[$index]['__computed_key'] = $computedKey;
        }

        foreach (array_values($normalized) as $column) {
            if (($column['column_type'] ?? null) !== 'belt_change_manual') {
                continue;
            }

            $sourceKey = trim((string) ($column['source_column_key'] ?? ''));

            if ($sourceKey === '') {
                continue;
            }

            $currentIndex = collect($normalized)->search(
                fn ($item) => ($item['__computed_key'] ?? null) === ($column['__computed_key'] ?? null)
            );
            $sourceIndex = collect($normalized)->search(
                fn ($item) => ($item['__computed_key'] ?? null) === $sourceKey
            );

            if ($currentIndex === false || $sourceIndex === false || $currentIndex < $sourceIndex) {
                continue;
            }

            $item = $normalized[$currentIndex];
            array_splice($normalized, $currentIndex, 1);

            $sourceIndex = collect($normalized)->search(
                fn ($entry) => ($entry['__computed_key'] ?? null) === $sourceKey
            );

            if ($sourceIndex === false) {
                $normalized[] = $item;
                continue;
            }

            array_splice($normalized, $sourceIndex, 0, [$item]);
        }

        return array_map(function (array $column, int $index) {
            unset($column['__computed_key']);
            $column['position'] = $index;

            return $column;
        }, $normalized, array_keys($normalized));
    }
}
