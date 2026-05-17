<?php

namespace App\Repositories\Audit;

use App\Models\AuditLog;
use App\Repositories\BaseRepository;

class AuditRepository extends BaseRepository
{
    /**
     * Store a new audit log entry.
     *
     * @param array $data
     * @return AuditLog
     */
    public function store(array $data): AuditLog
    {
        return AuditLog::create($data);
    }
}
