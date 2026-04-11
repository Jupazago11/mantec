<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ElementType;
use App\Models\ReportDetail;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $roleKey = $user->role?->key;

        abort_unless(
            in_array($roleKey, [
                'superadmin',
                'admin_global',
                'admin',
                'admin_cliente',
                'observador',
                'observador_cliente',
            ], true),
            403,
            'Rol no autorizado.'
        );

        $clients = $this->getScopedClients($user);

        $generalReportModules = $this->buildGeneralModules($user, $clients);
        $reportModules = $this->buildElementTypeModules($user, $clients);

        return view('admin.dashboard.admin', [
            'generalReportModules' => $generalReportModules,
            'reportModules' => $reportModules,
            'isReadOnly' => $this->isReadOnlyRole($user),
            'roleKey' => $roleKey,
        ]);
    }

    private function buildGeneralModules($user, Collection $clients): Collection
    {
        return $clients
            ->map(function ($client) use ($user) {
                $years = $this->getAvailableYearsForClient($user, (int) $client->id);

                return collect($years)->map(function ($year) use ($client) {
                    return [
                        'mode' => 'general',
                        'client_id' => $client->id,
                        'client_name' => $client->name,
                        'title' => 'Reporte preventivo general Planta ' . $client->name . ' ' . $year,
                        'year' => $year,
                    ];
                });
            })
            ->flatten(1)
            ->sortBy([
                ['client_name', 'asc'],
                ['year', 'desc'],
            ])
            ->values();
    }

    private function buildElementTypeModules($user, Collection $clients): Collection
    {
        return $clients
            ->map(function ($client) use ($user) {
                $elementTypes = $this->getScopedElementTypesForClient($user, (int) $client->id);

                return $elementTypes->map(function ($elementType) use ($user, $client) {
                    $years = $this->getAvailableYearsForClientAndElementType(
                        $user,
                        (int) $client->id,
                        (int) $elementType->id
                    );

                    return collect($years)->map(function ($year) use ($client, $elementType) {
                        return [
                            'mode' => 'single',
                            'client_id' => $client->id,
                            'client_name' => $client->name,
                            'element_type_id' => $elementType->id,
                            'element_type_name' => $elementType->name,
                            'title' => 'Reporte preventivo ' . $elementType->name . ' Planta ' . $client->name . ' ' . $year,
                            'year' => $year,
                        ];
                    });
                })->flatten(1);
            })
            ->flatten(1)
            ->sortBy([
                ['client_name', 'asc'],
                ['element_type_name', 'asc'],
                ['year', 'desc'],
            ])
            ->values();
    }

    private function getScopedClients($user): Collection
    {
        $roleKey = $user->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'observador'], true)) {
            return Client::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $user->clients()
            ->where('clients.status', true)
            ->orderBy('clients.name')
            ->get(['clients.id', 'clients.name']);
    }

    private function getScopedElementTypesForClient($user, int $clientId): Collection
    {
        if (!$this->mustRestrictByElementTypes($user)) {
            return ElementType::query()
                ->where('client_id', $clientId)
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'client_id', 'name']);
        }

        $allowedElementTypeIds = $user->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.status', true)
            ->pluck('element_types.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($allowedElementTypeIds)) {
            return collect();
        }

        return ElementType::query()
            ->where('client_id', $clientId)
            ->where('status', true)
            ->whereIn('id', $allowedElementTypeIds)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name']);
    }

    private function getAvailableYearsForClient($user, int $clientId): array
    {
        $query = ReportDetail::query()
            ->whereHas('element', function ($elementQuery) use ($user, $clientId) {
                $elementQuery->whereHas('area', function ($areaQuery) use ($clientId) {
                    $areaQuery->where('client_id', $clientId);
                });

                if ($this->mustRestrictByElementTypes($user)) {
                    $allowedElementTypeIds = $this->getAllowedElementTypeIdsForClient($user, $clientId);

                    if (empty($allowedElementTypeIds)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->whereIn('element_type_id', $allowedElementTypeIds);
                    }
                }

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaMap = $this->getAllowedAreaIdsGroupedByElementType($user, $clientId);

                    if (empty($allowedAreaMap)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->where(function ($outer) use ($allowedAreaMap) {
                            foreach ($allowedAreaMap as $elementTypeId => $areaIds) {
                                $outer->orWhere(function ($inner) use ($elementTypeId, $areaIds) {
                                    $inner->where('element_type_id', (int) $elementTypeId)
                                        ->whereIn('area_id', $areaIds);
                                });
                            }
                        });
                    }
                }
            });

        return $this->normalizeYearsWithCurrent($query->pluck('year')->all());
    }

    private function getAvailableYearsForClientAndElementType($user, int $clientId, int $elementTypeId): array
    {
        $query = ReportDetail::query()
            ->whereHas('element', function ($elementQuery) use ($user, $clientId, $elementTypeId) {
                $elementQuery->where('element_type_id', $elementTypeId)
                    ->whereHas('area', function ($areaQuery) use ($clientId) {
                        $areaQuery->where('client_id', $clientId);
                    });

                if ($this->mustRestrictByAreas($user)) {
                    $allowedAreaIds = $this->getAllowedAreaIdsForClientAndElementType(
                        $user,
                        $clientId,
                        $elementTypeId
                    );

                    if (empty($allowedAreaIds)) {
                        $elementQuery->whereRaw('1 = 0');
                    } else {
                        $elementQuery->whereIn('area_id', $allowedAreaIds);
                    }
                }
            });

        return $this->normalizeYearsWithCurrent($query->pluck('year')->all());
    }
    
    private function isReadOnlyRole($user): bool
    {
        return in_array($user->role?->key, ['observador', 'observador_cliente'], true);
    }

    private function mustRestrictByElementTypes($user): bool
    {
        return in_array($user->role?->key, ['admin_cliente', 'observador_cliente'], true);
    }

    private function mustRestrictByAreas($user): bool
    {
        return $user->role?->key === 'admin_cliente';
    }

    private function getAllowedElementTypeIdsForClient($user, int $clientId): array
    {
        if (!$this->mustRestrictByElementTypes($user)) {
            return ElementType::query()
                ->where('client_id', $clientId)
                ->where('status', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $user->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.status', true)
            ->pluck('element_types.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getAllowedAreaIdsForClientAndElementType($user, int $clientId, int $elementTypeId): array
    {
        if (!$this->mustRestrictByAreas($user)) {
            return [];
        }

        return $user->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('element_type_id', $elementTypeId)
            ->pluck('areas.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function getAllowedAreaIdsGroupedByElementType($user, int $clientId): array
    {
        if (!$this->mustRestrictByAreas($user)) {
            return [];
        }

        return $user->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->get(['areas.id'])
            ->groupBy(fn ($area) => (int) $area->pivot->element_type_id)
            ->map(function ($group) {
                return $group->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            })
            ->toArray();
    }

    private function normalizeYearsWithCurrent(array $years): array
    {
        $normalized = collect($years)
            ->filter(fn ($year) => $year !== null && $year !== '')
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $currentYear = (int) now()->year;

        if (!in_array($currentYear, $normalized, true)) {
            $normalized[] = $currentYear;
            rsort($normalized, SORT_NUMERIC);
        }

        return array_values(array_unique($normalized));
    }
}
