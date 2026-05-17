<?php

namespace App\Listeners;

use App\Services\Audit\AuditService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Logout event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        $this->auditService->log(
            module: 'Auth',
            action: 'Logout',
            description: "User {$user->name} ({$user->email}) successfully logged out.",
            userId: $user->id
        );
    }
}
