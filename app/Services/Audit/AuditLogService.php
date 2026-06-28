<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function __construct(
        protected AuditLogRepository $repository,
    ) {}

    public function log(
        AuditAction|string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $properties = null,
        ?User $user = null,
        ?Request $request = null,
    ): void {
        $request ??= request();
        $user ??= Auth::user();
        $actionValue = $action instanceof AuditAction ? $action->value : $action;

        $this->repository->create([
            'user_id' => $user?->id,
            'action' => $actionValue,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function logCreated(Model $model, ?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Create, $model, 'Created '.class_basename($model), [
            'new' => $model->getAttributes(),
        ], $user, $request);
    }

    public function logUpdated(Model $model, array $oldValues, ?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Update, $model, 'Updated '.class_basename($model), [
            'old' => $oldValues,
            'new' => $model->getAttributes(),
        ], $user, $request);
    }

    public function logDeleted(Model $model, ?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Delete, $model, 'Deleted '.class_basename($model), [
            'old' => $model->getAttributes(),
        ], $user, $request);
    }

    public function logAttendance(Model $model, ?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Attendance, $model, 'Attendance recorded', null, $user, $request);
    }

    public function logLogin(?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Login, $user, 'User logged in', null, $user, $request);
    }

    public function logLogout(?User $user = null, ?Request $request = null): void
    {
        $this->log(AuditAction::Logout, $user, 'User logged out', null, $user, $request);
    }
}
