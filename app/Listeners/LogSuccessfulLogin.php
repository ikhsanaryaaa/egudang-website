<?php

namespace App\Listeners;

use App\Services\Audit\AuditService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Login event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        $this->auditService->log(
            module: 'Auth',
            action: 'Login',
            description: "User {$user->name} ({$user->email}) successfully logged in.",
            userId: $user->id
        );
    }
}
