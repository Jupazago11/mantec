<div id="usersListContainer">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Nombre</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'names')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('names') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Usuario
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Rol</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'role_keys')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('role_keys') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    @if($showClientColumn)
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <div class="flex items-center gap-2">
                                <span>Clientes</span>
                                <button
                                    type="button"
                                    onclick="openFilterPopover(event, 'client_ids')"
                                    class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('client_ids') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                    </svg>
                                </button>
                            </div>
                        </th>
                    @endif

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Permisos
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <div class="flex items-center gap-2">
                            <span>Estado</span>
                            <button
                                type="button"
                                onclick="openFilterPopover(event, 'statuses')"
                                class="rounded p-1 transition hover:bg-slate-200 {{ $hasFilter('statuses') ? 'text-[#d94d33]' : 'text-slate-400' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                                </svg>
                            </button>
                        </div>
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Acciones
                    </th>
                </tr>
            </thead>

            <tbody id="usersTableBody" class="divide-y divide-slate-200 bg-white">
                @forelse($users as $user)
                    @php
                        $roleKey = $user->role?->key;
                        $authRoleKey = auth()->user()?->role?->key;
                        $specializedMap = $user->allowedElementTypes->groupBy(fn($item) => $item->pivot->client_id);
                        $isSelf = (int) $user->id === (int) $authUserId;
                        $canSelfEditProfile = $isSelf && in_array($authRoleKey, ['superadmin', 'admin_global'], true);

                        $canManage = false;

                        if (!$isSelf) {
                            if (in_array($authRoleKey, ['superadmin', 'admin_global'], true)) {
                                $canManage = in_array($roleKey, ['admin', 'admin_cliente', 'inspector', 'observador', 'observador_cliente'], true);
                            } else {
                                $canManage = in_array($roleKey, ['admin_cliente', 'inspector', 'observador', 'observador_cliente'], true);
                            }
                        }
                    @endphp

                    <tr class="hover:bg-slate-50" id="user-row-{{ $user->id }}">
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                            {{ $user->name }}
                            @if($isSelf)
                                <span class="ml-2 rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">Tú</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                            {{ $user->username }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-700">
                            {{ $user->role?->name ?? '—' }}
                        </td>

                        @if($showClientColumn)
                            <td class="px-5 py-3 text-sm text-slate-700">
                                {{ $user->clients->pluck('name')->implode(', ') ?: '—' }}
                            </td>
                        @endif

                        <td class="px-5 py-3 text-sm text-slate-700">
                            @if(in_array($roleKey, ['admin_cliente', 'inspector', 'observador_cliente'], true))
                                @php
                                    $groupsByClientForUser = $user->groups->groupBy('client_id');
                                @endphp

                                @if($groupsByClientForUser->isNotEmpty())
                                    <div class="space-y-1">
                                        @foreach($user->clients as $client)
                                            @php
                                                $groups = $groupsByClientForUser->get($client->id, collect());
                                            @endphp

                                            @if($groups->isNotEmpty())
                                                <div>
                                                    <span class="font-semibold text-slate-900">{{ $client->name }}:</span>
                                                    {{ $groups->pluck('name')->implode(', ') }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            @elseif(in_array($roleKey, ['observador'], true) && $specializedMap->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach($user->clients as $client)
                                        @php
                                            $types = $specializedMap->get($client->id, collect());
                                        @endphp

                                        @if($types->isNotEmpty())
                                            <div>
                                                <span class="font-semibold text-slate-900">{{ $client->name }}:</span>
                                                {{ $types->pluck('name')->implode(', ') }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                —
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm">
                            @if($canManage)
                                <button
                                    type="button"
                                    onclick="toggleUserStatus(this, this.dataset.url)"
                                    data-url="{{ route('admin.managed-users.toggle-status', $user) }}"
                                    data-status="{{ $user->status ? '1' : '0' }}"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $user->status ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                    title="Clic para cambiar estado"
                                >
                                    <i data-lucide="{{ $user->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                    <span>{{ $user->status ? 'Activo' : 'Inactivo' }}</span>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $user->status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    <i data-lucide="{{ $user->status ? 'check-circle-2' : 'x-circle' }}" class="h-3.5 w-3.5"></i>
                                    <span>{{ $user->status ? 'Activo' : 'Inactivo' }}</span>
                                </span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                @php
                                    $editPayload = [
                                        'id' => $user->id,
                                        'name' => $user->name,
                                        'document' => $user->document,
                                        'username' => $user->username,
                                        'role_id' => $user->role_id,
                                        'role_key' => $user->role?->key,
                                        'clients' => $user->clients->pluck('id')->values()->toArray(),
                                        'permissions' => $user->allowedElementTypes
                                            ->groupBy(fn ($item) => $item->pivot->client_id)
                                            ->map(fn ($group) => $group->pluck('id')->values()->toArray())
                                            ->toArray(),
                                        'area_permissions' => $user->allowedAreas
                                            ->groupBy(fn ($item) => $item->pivot->client_id)
                                            ->map(function ($groupByClient) {
                                                return $groupByClient
                                                    ->groupBy(fn ($item) => $item->pivot->element_type_id)
                                                    ->map(fn ($groupByType) => $groupByType->pluck('id')->values()->toArray())
                                                    ->toArray();
                                            })
                                            ->toArray(),
                                        'group_permissions' => $user->groups
                                            ->groupBy('client_id')
                                            ->map(fn ($groups) => $groups->pluck('id')->values()->toArray())
                                            ->toArray(),
                                        'group_area_permissions' => $user->allowedGroupAreas
                                            ->groupBy(fn ($item) => $item->pivot->client_id)
                                            ->map(function ($groupByClient) {
                                                return $groupByClient
                                                    ->groupBy(fn ($item) => $item->pivot->group_id)
                                                    ->map(fn ($groupByGroup) => $groupByGroup->pluck('id')->values()->toArray())
                                                    ->toArray();
                                            })
                                            ->toArray(),
                                        'action' => route('admin.managed-users.update', $user),
                                        'is_self' => $isSelf,
                                        'can_self_edit_profile' => $canSelfEditProfile,
                                        'can_manage' => $canManage,
                                    ];
                                @endphp

                                @if($isSelf || $canManage)
                                    <button
                                        type="button"
                                        class="text-slate-400 transition hover:text-[#d94d33]"
                                        onclick='openEditUserModal(@json($editPayload))'
                                        title="{{ $isSelf ? ($canSelfEditProfile ? 'Editar perfil' : 'Cambiar contraseña') : 'Editar usuario' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 113 3l-1.651 1.651M4 20h4l10.586-10.586a2 2 0 00-2.828-2.828L5.172 17.172A2 2 0 004 18.586V20z" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="inline-flex items-center text-slate-300" title="Solo lectura">
                                        <i data-lucide="lock" class="h-4 w-4"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showClientColumn ? 7 : 6 }}" class="px-5 py-10 text-center text-sm text-slate-500">
                            No hay usuarios registrados todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 px-6 py-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                @if($users->total() > 0)
                    Mostrando {{ $users->firstItem() }} a {{ $users->lastItem() }} de {{ $users->total() }} resultados
                @else
                    Mostrando 0 resultados
                @endif
            </p>

            @if($users->hasPages())
                <nav class="flex items-center gap-2" aria-label="Paginación de usuarios">
                    @php
                        $currentPage = $users->currentPage();
                        $lastPage = $users->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    <a
                        href="{{ $users->previousPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $users->onFirstPage() ? 'pointer-events-none opacity-50' : '' }}"
                        aria-label="Página anterior"
                    >
                        ‹
                    </a>

                    @for($page = $startPage; $page <= $endPage; $page++)
                        <a
                            href="{{ $users->url($page) }}"
                            data-pagination-link
                            class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold transition {{ $page === $currentPage ? 'bg-slate-800 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}"
                            aria-current="{{ $page === $currentPage ? 'page' : 'false' }}"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    <a
                        href="{{ $users->nextPageUrl() ?: '#' }}"
                        data-pagination-link
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 text-slate-600 transition hover:bg-slate-100 {{ $users->hasMorePages() ? '' : 'pointer-events-none opacity-50' }}"
                        aria-label="Página siguiente"
                    >
                        ›
                    </a>
                </nav>
            @endif
        </div>
    </div>
</div>
