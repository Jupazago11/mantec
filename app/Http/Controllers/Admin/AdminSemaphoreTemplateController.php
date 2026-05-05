<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Component;
use App\Models\Diagnostic;
use App\Models\ElementType;
use App\Models\Group;
use App\Models\SemaphoreTemplate;
use App\Models\SemaphoreTemplateColumn;
use App\Services\Semaphore\SemaphoreColumnOrderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSemaphoreTemplateController extends Controller
{
    public function __construct(
        private readonly SemaphoreColumnOrderer $semaphoreColumnOrderer,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeManage();

        $clients = $this->scopedClients();
        $groups = $this->scopedGroups($clients->pluck('id')->all());
        $elementTypes = $this->scopedElementTypes($clients->pluck('id')->all());

        if (!$this->hasSemaphoreTemplateTables()) {
            return view('admin.semaphore-templates.index', [
                'clients' => $clients,
                'groups' => $groups,
                'elementTypes' => $elementTypes,
                'templates' => collect(),
                'migrationPending' => true,
                'prefill' => [
                    'client_id' => $request->query('client_id'),
                    'group_id' => $request->query('group_id'),
                    'element_type_id' => $request->query('element_type_id'),
                ],
            ]);
        }

        $templates = SemaphoreTemplate::query()
            ->with([
                'client:id,name',
                'group:id,client_id,name',
                'elementType:id,client_id,name',
                'columns.rules',
            ])
            ->whereIn('client_id', $clients->pluck('id'))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.semaphore-templates.index', [
            'clients' => $clients,
            'groups' => $groups,
            'elementTypes' => $elementTypes,
            'templates' => $templates,
            'prefill' => [
                'client_id' => $request->query('client_id'),
                'group_id' => $request->query('group_id'),
                'element_type_id' => $request->query('element_type_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();
        $this->ensureSemaphoreTemplateTables();

        $validated = $this->validateTemplate($request);
        $this->assertTemplateScopeConsistency($validated);

        $template = DB::transaction(function () use ($validated) {
            if (!empty($validated['is_default'])) {
                $this->clearDefaultTemplateInScope(
                    (int) $validated['client_id'],
                    isset($validated['group_id']) ? (int) $validated['group_id'] : null,
                    (int) $validated['element_type_id']
                );
            }

            return SemaphoreTemplate::create([
                'client_id' => (int) $validated['client_id'],
                'group_id' => !empty($validated['group_id']) ? (int) $validated['group_id'] : null,
                'element_type_id' => (int) $validated['element_type_id'],
                'name' => trim($validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'is_default' => !empty($validated['is_default']),
                'status' => true,
            ]);
        });

        if ($this->wantsJsonResponse($request)) {
            $template->loadMissing([
                'client:id,name',
                'group:id,client_id,name',
                'elementType:id,client_id,name',
                'columns',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plantilla de semaforo creada correctamente.',
                'row' => $this->serializeTemplateRow($template),
                'edit_url' => route('admin.semaphore-templates.edit', $template),
            ], Response::HTTP_CREATED);
        }

        return redirect()
            ->route('admin.semaphore-templates.edit', $template)
            ->with('success', 'Plantilla de semaforo creada correctamente. Ahora configura sus columnas.');
    }

    public function edit(Request $request, SemaphoreTemplate $semaphoreTemplate): View|JsonResponse
    {
        $this->authorizeManage();
        $this->ensureSemaphoreTemplateTables();
        $this->authorizeTemplateScope($semaphoreTemplate);

        $semaphoreTemplate->load([
            'client:id,name',
            'group:id,client_id,name',
            'elementType:id,client_id,name',
            'columns.rules.component:id,client_id,element_type_id,name,code',
            'columns.rules.diagnostic:id,client_id,element_type_id,name',
        ]);

        $clients = $this->scopedClients();
        $groups = $this->scopedGroups($clients->pluck('id')->all());
        $elementTypes = $this->scopedElementTypes($clients->pluck('id')->all());
        $components = Component::query()
            ->with([
                'diagnostics' => fn ($query) => $query
                    ->where('status', true)
                    ->where('client_id', $semaphoreTemplate->client_id)
                    ->where('element_type_id', $semaphoreTemplate->element_type_id)
                    ->orderBy('name'),
            ])
            ->where('status', true)
            ->where('client_id', $semaphoreTemplate->client_id)
            ->where('element_type_id', $semaphoreTemplate->element_type_id)
            ->orderBy('name')
            ->get(['id', 'client_id', 'element_type_id', 'name', 'code']);
        $diagnostics = Diagnostic::query()
            ->where('status', true)
            ->where('client_id', $semaphoreTemplate->client_id)
            ->where('element_type_id', $semaphoreTemplate->element_type_id)
            ->orderBy('name')
            ->get(['id', 'client_id', 'element_type_id', 'name']);

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'template' => $this->serializeTemplateEditor($semaphoreTemplate, $components, $diagnostics),
                'options' => [
                    'column_types' => $this->columnTypeOptions(),
                    'directions' => $this->severityDirectionOptions(),
                    'empty_states' => $this->emptyStateOptions(),
                ],
            ]);
        }

        return view('admin.semaphore-templates.edit', [
            'template' => $semaphoreTemplate,
            'clients' => $clients,
            'groups' => $groups,
            'elementTypes' => $elementTypes,
            'components' => $components,
            'diagnostics' => $diagnostics,
            'columnTypeOptions' => $this->columnTypeOptions(),
            'directionOptions' => $this->severityDirectionOptions(),
            'emptyStateOptions' => $this->emptyStateOptions(),
        ]);
    }

    public function update(Request $request, SemaphoreTemplate $semaphoreTemplate): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();
        $this->ensureSemaphoreTemplateTables();
        $this->authorizeTemplateScope($semaphoreTemplate);

        $validated = $this->validateTemplate($request, $semaphoreTemplate->id, true);
        $this->assertTemplateScopeConsistency($validated);

        DB::transaction(function () use ($request, $validated, $semaphoreTemplate) {
            if (!empty($validated['is_default'])) {
                $this->clearDefaultTemplateInScope(
                    (int) $validated['client_id'],
                    isset($validated['group_id']) ? (int) $validated['group_id'] : null,
                    (int) $validated['element_type_id'],
                    $semaphoreTemplate->id
                );
            }

            $this->assertColumnRulesConsistency(
                (int) $validated['client_id'],
                (int) $validated['element_type_id'],
                $validated['columns'] ?? []
            );

            $semaphoreTemplate->update([
                'client_id' => (int) $validated['client_id'],
                'group_id' => !empty($validated['group_id']) ? (int) $validated['group_id'] : null,
                'element_type_id' => (int) $validated['element_type_id'],
                'name' => trim($validated['name']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'is_default' => !empty($validated['is_default']),
                'status' => $request->boolean('status', true),
            ]);

            $this->syncColumns($semaphoreTemplate, $validated['columns'] ?? []);
        });

        if ($this->wantsJsonResponse($request)) {
            $semaphoreTemplate->refresh()->loadMissing([
                'client:id,name',
                'group:id,client_id,name',
                'elementType:id,client_id,name',
                'columns.rules',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plantilla de semaforo actualizada correctamente.',
                'row' => $this->serializeTemplateRow($semaphoreTemplate),
            ]);
        }

        return redirect()
            ->route('admin.semaphore-templates.edit', $semaphoreTemplate)
            ->with('success', 'Plantilla de semaforo actualizada correctamente.');
    }

    public function destroy(Request $request, SemaphoreTemplate $semaphoreTemplate): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();
        $this->ensureSemaphoreTemplateTables();
        $this->authorizeTemplateScope($semaphoreTemplate);

        $semaphoreTemplate->delete();

        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Plantilla de semaforo eliminada correctamente.',
                'id' => $semaphoreTemplate->id,
            ]);
        }

        return redirect()
            ->route('admin.semaphore-templates.index')
            ->with('success', 'Plantilla de semaforo eliminada correctamente.');
    }

    public function toggleStatus(Request $request, SemaphoreTemplate $semaphoreTemplate): RedirectResponse|JsonResponse
    {
        $this->authorizeManage();
        $this->ensureSemaphoreTemplateTables();
        $this->authorizeTemplateScope($semaphoreTemplate);

        $semaphoreTemplate->update([
            'status' => !$semaphoreTemplate->status,
        ]);

        if ($this->wantsJsonResponse($request)) {
            $semaphoreTemplate->refresh()->loadMissing([
                'client:id,name',
                'group:id,client_id,name',
                'elementType:id,client_id,name',
                'columns',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de la plantilla actualizado correctamente.',
                'row' => $this->serializeTemplateRow($semaphoreTemplate),
            ]);
        }

        return redirect()
            ->route('admin.semaphore-templates.edit', $semaphoreTemplate)
            ->with('success', 'Estado de la plantilla actualizado correctamente.');
    }

    private function authorizeManage(): void
    {
        abort_unless(in_array(auth()->user()?->role?->key, [
            'superadmin',
            'admin_global',
            'admin',
            'admin_cliente',
        ], true), 403);
    }

    private function hasSemaphoreTemplateTables(): bool
    {
        return Schema::hasTable('semaphore_templates')
            && Schema::hasTable('semaphore_template_columns')
            && Schema::hasTable('semaphore_template_column_rules');
    }

    private function ensureSemaphoreTemplateTables(): void
    {
        abort_unless(
            $this->hasSemaphoreTemplateTables(),
            409,
            'Faltan las tablas del modulo de plantillas de semaforo. Ejecuta las migraciones pendientes.'
        );
    }

    private function scopedClients(): Collection
    {
        $user = auth()->user();
        $roleKey = $user?->role?->key;

        if (in_array($roleKey, ['superadmin', 'admin_global', 'admin'], true)) {
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

    private function scopedGroups(array $clientIds): Collection
    {
        $user = auth()->user();
        $roleKey = $user?->role?->key;

        $query = Group::query()
            ->where('status', true)
            ->whereIn('client_id', $clientIds)
            ->orderBy('name');

        if ($roleKey === 'admin_cliente') {
            $allowedGroupIds = $user->groups()->pluck('groups.id')->all();
            $query->whereIn('id', $allowedGroupIds);
        }

        return $query->get(['id', 'client_id', 'name']);
    }

    private function scopedElementTypes(array $clientIds): Collection
    {
        return ElementType::query()
            ->where('status', true)
            ->whereIn('client_id', $clientIds)
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'has_semaphore']);
    }

    private function authorizeTemplateScope(SemaphoreTemplate $template): void
    {
        abort_unless(
            $this->scopedClients()->pluck('id')->contains($template->client_id),
            403
        );
    }

    private function validateTemplate(Request $request, ?int $templateId = null, bool $withColumns = false): array
    {
        $rules = [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'element_type_id' => ['required', 'integer', 'exists:element_types,id'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('semaphore_templates', 'name')->where(function ($query) use ($request) {
                    return $query
                        ->where('client_id', $request->input('client_id'))
                        ->where('group_id', $request->input('group_id'))
                        ->where('element_type_id', $request->input('element_type_id'));
                })->ignore($templateId),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ];

        if ($withColumns) {
            $rules = array_merge($rules, [
                'columns' => ['nullable', 'array'],
                'columns.*.id' => ['nullable', 'integer'],
                'columns.*.label' => ['required', 'string', 'max:120'],
                'columns.*.key' => ['nullable', 'string', 'max:120'],
                'columns.*.description' => ['nullable', 'string', 'max:500'],
                'columns.*.column_type' => ['required', Rule::in(array_keys($this->columnTypeOptions()))],
                'columns.*.severity_direction' => ['required', Rule::in(array_keys($this->severityDirectionOptions()))],
                'columns.*.empty_state_behavior' => ['required', Rule::in(array_keys($this->emptyStateOptions()))],
                'columns.*.source_column_key' => ['nullable', 'string', 'max:120'],
                'columns.*.position' => ['nullable', 'integer', 'min:0'],
                'columns.*.status' => ['nullable', 'boolean'],
                'columns.*.rules' => ['nullable', 'array'],
                'columns.*.rules.*.component_id' => ['required', 'integer', 'exists:components,id'],
                'columns.*.rules.*.diagnostic_id' => ['required', 'integer', 'exists:diagnostics,id'],
            ]);
        }

        return $request->validate($rules, [
            'name.unique' => 'Ya existe una plantilla con ese nombre para el mismo cliente, agrupacion y tipo de activo.',
        ]);
    }

    private function assertTemplateScopeConsistency(array $validated): void
    {
        $clientId = (int) $validated['client_id'];
        $elementType = ElementType::query()->findOrFail((int) $validated['element_type_id']);

        abort_unless((int) $elementType->client_id === $clientId, 422, 'El tipo de activo no pertenece al cliente seleccionado.');

        if (!empty($validated['group_id'])) {
            $group = Group::query()->findOrFail((int) $validated['group_id']);
            abort_unless((int) $group->client_id === $clientId, 422, 'La agrupacion no pertenece al cliente seleccionado.');
        }
    }

    private function clearDefaultTemplateInScope(int $clientId, ?int $groupId, int $elementTypeId, ?int $ignoreId = null): void
    {
        $query = SemaphoreTemplate::query()
            ->where('client_id', $clientId)
            ->where('group_id', $groupId)
            ->where('element_type_id', $elementTypeId)
            ->where('is_default', true);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $query->update(['is_default' => false]);
    }

    private function syncColumns(SemaphoreTemplate $template, array $columns): void
    {
        $columns = $this->semaphoreColumnOrderer->normalizePayload($columns);
        $existingIds = $template->columns()->pluck('id')->all();
        $keptIds = [];

        foreach (array_values($columns) as $index => $columnData) {
            $columnId = isset($columnData['id']) ? (int) $columnData['id'] : null;
            $key = Str::slug(trim((string) ($columnData['key'] ?? ''))) ?: Str::slug(trim($columnData['label']));

            /** @var SemaphoreTemplateColumn $column */
            $column = $template->columns()->updateOrCreate(
                ['id' => $columnId],
                [
                    'key' => $key ?: 'column-' . ($index + 1),
                    'label' => trim($columnData['label']),
                    'description' => trim((string) ($columnData['description'] ?? '')) ?: null,
                    'column_type' => $columnData['column_type'],
                    'severity_direction' => $columnData['severity_direction'],
                    'empty_state_behavior' => $columnData['empty_state_behavior'],
                    'source_column_key' => trim((string) ($columnData['source_column_key'] ?? '')) ?: null,
                    'position' => isset($columnData['position']) ? (int) $columnData['position'] : $index,
                    'status' => array_key_exists('status', $columnData) ? (bool) $columnData['status'] : true,
                ]
            );

            $keptIds[] = $column->id;

            $rules = collect($columnData['rules'] ?? [])
                ->filter(fn ($rule) => !empty($rule['component_id']) && !empty($rule['diagnostic_id']))
                ->values();

            $column->rules()->delete();

            foreach ($rules as $ruleIndex => $rule) {
                $column->rules()->create([
                    'component_id' => (int) $rule['component_id'],
                    'diagnostic_id' => (int) $rule['diagnostic_id'],
                    'position' => $ruleIndex,
                ]);
            }
        }

        $deleteIds = array_diff($existingIds, $keptIds);

        if (!empty($deleteIds)) {
            $template->columns()->whereIn('id', $deleteIds)->delete();
        }
    }

    private function assertColumnRulesConsistency(int $clientId, int $elementTypeId, array $columns): void
    {
        $columnKeys = [];

        foreach ($columns as $columnIndex => $column) {
            $computedKey = Str::slug(trim((string) ($column['key'] ?? ''))) ?: Str::slug(trim((string) ($column['label'] ?? '')));

            if ($computedKey !== '') {
                abort_unless(
                    !in_array($computedKey, $columnKeys, true),
                    422,
                    'La clave interna de la columna ' . ($columnIndex + 1) . ' esta duplicada dentro de la plantilla.'
                );

                $columnKeys[] = $computedKey;
            }

            $rulePairs = [];

            foreach (($column['rules'] ?? []) as $ruleIndex => $rule) {
                $componentId = isset($rule['component_id']) ? (int) $rule['component_id'] : 0;
                $diagnosticId = isset($rule['diagnostic_id']) ? (int) $rule['diagnostic_id'] : 0;

                if ($componentId <= 0 || $diagnosticId <= 0) {
                    continue;
                }

                $pairKey = $componentId . ':' . $diagnosticId;

                abort_unless(
                    !in_array($pairKey, $rulePairs, true),
                    422,
                    'La regla ' . ($ruleIndex + 1) . ' de la columna ' . ($columnIndex + 1) . ' esta duplicada.'
                );

                $rulePairs[] = $pairKey;

                $component = Component::query()->findOrFail($componentId);
                $diagnostic = Diagnostic::query()->findOrFail($diagnosticId);

                abort_unless(
                    (int) $component->client_id === $clientId && (int) $component->element_type_id === $elementTypeId,
                    422,
                    'El componente de la regla ' . ($ruleIndex + 1) . ' de la columna ' . ($columnIndex + 1) . ' no pertenece al alcance seleccionado.'
                );

                abort_unless(
                    (int) $diagnostic->client_id === $clientId && (int) $diagnostic->element_type_id === $elementTypeId,
                    422,
                    'El diagnostico de la regla ' . ($ruleIndex + 1) . ' de la columna ' . ($columnIndex + 1) . ' no pertenece al alcance seleccionado.'
                );

                abort_unless(
                    $component->diagnostics()->where('diagnostics.id', $diagnosticId)->exists(),
                    422,
                    'El diagnostico de la regla ' . ($ruleIndex + 1) . ' de la columna ' . ($columnIndex + 1) . ' no esta asociado al componente.'
                );
            }
        }
    }

    private function columnTypeOptions(): array
    {
        return [
            'condition_aggregate' => 'Condicion agregada por componentes',
            'belt_change_manual' => 'Cambio de banda (manual / reporte)',
        ];
    }

    private function severityDirectionOptions(): array
    {
        return [
            'asc' => 'Ascendente: 1 es mas critico',
            'desc' => 'Descendente: el mayor valor es mas critico',
        ];
    }

    private function emptyStateOptions(): array
    {
        return [
            'neutral' => 'N/A neutro',
            'ok' => 'N/A verde',
        ];
    }

    private function serializeTemplateRow(SemaphoreTemplate $template): array
    {
        return [
            'id' => $template->id,
            'client_id' => $template->client_id,
            'group_id' => $template->group_id,
            'element_type_id' => $template->element_type_id,
            'name' => $template->name,
            'description' => $template->description,
            'is_default' => (bool) $template->is_default,
            'status' => (bool) $template->status,
            'client_name' => $template->client?->name,
            'group_name' => $template->group?->name,
            'element_type_name' => $template->elementType?->name,
            'columns_count' => $template->relationLoaded('columns')
                ? $template->columns->count()
                : $template->columns()->count(),
            'edit_url' => route('admin.semaphore-templates.edit', $template),
            'toggle_url' => route('admin.semaphore-templates.toggle-status', $template),
            'destroy_url' => route('admin.semaphore-templates.destroy', $template),
        ];
    }

    private function serializeTemplateEditor(SemaphoreTemplate $template, Collection $components, Collection $diagnostics): array
    {
        $orderedColumns = $this->semaphoreColumnOrderer->orderCollection($template->columns);

        return [
            'id' => $template->id,
            'client_id' => $template->client_id,
            'group_id' => $template->group_id,
            'element_type_id' => $template->element_type_id,
            'name' => $template->name,
            'description' => $template->description,
            'is_default' => (bool) $template->is_default,
            'status' => (bool) $template->status,
            'client_name' => $template->client?->name,
            'group_name' => $template->group?->name,
            'element_type_name' => $template->elementType?->name,
            'update_url' => route('admin.semaphore-templates.update', $template),
            'columns' => $orderedColumns->map(function ($column) {
                return [
                    'id' => $column->id,
                    'label' => $column->label,
                    'key' => $column->key,
                    'description' => $column->description,
                    'column_type' => $column->column_type,
                    'severity_direction' => $column->severity_direction,
                    'empty_state_behavior' => $column->empty_state_behavior,
                    'source_column_key' => $column->source_column_key,
                    'position' => $column->position,
                    'status' => (bool) $column->status,
                    'rules' => $column->rules->map(function ($rule) {
                        return [
                            'component_id' => $rule->component_id,
                            'diagnostic_id' => $rule->diagnostic_id,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'components' => $components->map(fn ($item) => [
                'id' => $item->id,
                'label' => trim(($item->code ? $item->code . ' - ' : '') . $item->name),
                'diagnostic_ids' => $item->diagnostics->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ])->values()->all(),
            'diagnostics' => $diagnostics->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->name,
            ])->values()->all(),
        ];
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
