<?php

namespace App\Services\Audit;

use App\Repositories\Audit\AuditRepository;
use App\Services\BaseService;

class AuditService extends BaseService
{
    protected AuditRepository $repository;

    public function __construct(AuditRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Record an audit log entry.
     *
     * @param string $module Module name (e.g. Auth, Product, Stock, File)
     * @param string $action Action type (e.g. Login, Create Data, Barang Masuk)
     * @param string $description Detailed description of the activity
     * @param int|null $userId User who performed the action (defaults to current auth user)
     * @return \App\Models\AuditLog
     */
    public function log(string $module, string $action, string $description, ?int $userId = null)
    {
        return $this->repository->store([
            'user_id' => $userId ?? auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
