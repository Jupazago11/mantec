<?php

namespace App\Services\Execution;

use App\Models\Condition;
use App\Models\ExecutionStatus;
use Illuminate\Support\Collection;

class ExecutionStatusResolver
{
    public function activeStatuses(): Collection
    {
        return ExecutionStatus::query()
            ->where('status', true)
            ->get();
    }

    public function findPendingStatus(?Collection $statuses = null): ?ExecutionStatus
    {
        $statuses ??= $this->activeStatuses();

        return $statuses->first(function ($status) {
            $name = mb_strtoupper(trim((string) ($status->name ?? '')));
            $code = mb_strtoupper(trim((string) ($status->code ?? '')));

            return in_array($name, ['PENDIENTE', 'PENDIENTE DE EJECUCIÓN', 'SIN EJECUTAR'], true)
                || in_array($code, ['PENDIENTE', 'PENDING', 'PEND'], true);
        });
    }

    public function findDoneStatus(?Collection $statuses = null): ?ExecutionStatus
    {
        $statuses ??= $this->activeStatuses();

        return $statuses->first(function ($status) {
            $name = mb_strtoupper(trim((string) ($status->name ?? '')));
            $code = mb_strtoupper(trim((string) ($status->code ?? '')));

            return in_array($name, ['EJECUTADO', 'EJECUTADA', 'REALIZADO', 'REALIZADA', 'FINALIZADO', 'FINALIZADA'], true)
                || in_array($code, ['EJECUTADO', 'DONE', 'REALIZADO', 'COMPLETADO'], true);
        });
    }

    public function findOkStatus(?Collection $statuses = null): ?ExecutionStatus
    {
        $statuses ??= $this->activeStatuses();

        return $statuses->first(function ($status) {
            $name = mb_strtoupper(trim((string) ($status->name ?? '')));
            $code = mb_strtoupper(trim((string) ($status->code ?? '')));

            return $name === 'OK' || $code === 'OK';
        });
    }

    public function resolveStatusIdForCondition(Condition $condition, ?Collection $statuses = null): ?int
    {
        $statuses ??= $this->activeStatuses();

        if ($this->isOkCondition($condition)) {
            return $this->findOkStatus($statuses)?->id;
        }

        return $this->findPendingStatus($statuses)?->id;
    }

    public function isOkCondition(Condition $condition): bool
    {
        return mb_strtolower(trim((string) $condition->code)) === 'ok';
    }

    public function isDoneStatusName(?string $statusName): bool
    {
        $statusUpper = mb_strtoupper(trim((string) $statusName));

        return in_array($statusUpper, [
            'EJECUTADO',
            'EJECUTADA',
            'FINALIZADO',
            'FINALIZADA',
            'REALIZADO',
            'REALIZADA',
        ], true);
    }

    public function isOkStatusName(?string $statusName): bool
    {
        return mb_strtoupper(trim((string) $statusName)) === 'OK';
    }
}
