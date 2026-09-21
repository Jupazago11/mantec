<?php

namespace App\Services\Access;

use App\Models\Element;
use App\Models\User;

class ElementAccessService
{
    public function canAccess(User $user, Element $element): bool
    {
        $element->loadMissing('area');

        if (!$element->area) {
            return false;
        }

        $roleKey = $user->role?->key;

        if ($roleKey === 'inspector') {
            $hasClientAccess = $user->clients()
                ->where('clients.id', $element->area->client_id)
                ->where('clients.status', true)
                ->exists();

            if (!$hasClientAccess || !$element->group_id) {
                return false;
            }

            return $user->groups()
                ->where('groups.id', $element->group_id)
                ->where('groups.client_id', $element->area->client_id)
                ->where('groups.status', true)
                ->exists();
        }

        return $user->clients()->where('clients.id', $element->area->client_id)->where('clients.status', true)->exists()
            && $user->allowedElementTypesForClient((int) $element->area->client_id)->exists()
            && $user->hasElementTypeAccess((int) $element->area->client_id, (int) $element->element_type_id);
    }
}
