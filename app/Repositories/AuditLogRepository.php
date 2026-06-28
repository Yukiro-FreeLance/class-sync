<?php

namespace App\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuditLogRepository
{
    public function __construct(
        protected AuditLog $model,
    ) {}

    public function create(array $data): AuditLog
    {
        return $this->model->newQuery()->create($data);
    }

    public function find(int $id): ?AuditLog
    {
        return $this->model->newQuery()->with('user')->find($id);
    }

    /**
     * @return LengthAwarePaginator<AuditLog>
     */
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function getForModel(string $modelType, int $modelId): Collection
    {
        return $this->model->newQuery()
            ->with('user')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function getForUser(int $userId, int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
