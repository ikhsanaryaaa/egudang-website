<?php

namespace App\Traits;

use App\Services\Audit\AuditService;

/**
 * Trait HasAuditLog
 *
 * Automatically logs Create, Update, and Delete events for any Eloquent model.
 * Attach this trait to models that need CRUD audit logging.
 *
 * Usage: use HasAuditLog; inside any Model class.
 */
trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            if (!auth()->check()) {
                return;
            }

            $modelName = class_basename($model);
            $identifier = $model->name ?? $model->sku ?? $model->id;

            app(AuditService::class)->log(
                module: $modelName,
                action: 'Create Data',
                description: "Created {$modelName} '{$identifier}' (ID: {$model->id})"
            );
        });

        static::updated(function ($model) {
            if (!auth()->check()) {
                return;
            }

            // Skip if only auto-generated timestamp fields changed
            $ignoredFields = ['updated_at', 'created_at'];
            $changes = collect($model->getChanges())->except($ignoredFields);

            if ($changes->isEmpty()) {
                return;
            }

            $modelName = class_basename($model);
            $identifier = $model->name ?? $model->sku ?? $model->id;

            $changeParts = [];
            foreach ($changes as $field => $newValue) {
                $oldValue = $model->getOriginal($field);
                $changeParts[] = "{$field}: '{$oldValue}' -> '{$newValue}'";
            }
            $changeString = implode(', ', $changeParts);

            app(AuditService::class)->log(
                module: $modelName,
                action: 'Update Data',
                description: "Updated {$modelName} '{$identifier}' (ID: {$model->id}). Changes: [{$changeString}]"
            );
        });

        static::deleting(function ($model) {
            if (!auth()->check()) {
                return;
            }

            $modelName = class_basename($model);
            $identifier = $model->name ?? $model->sku ?? $model->id;

            app(AuditService::class)->log(
                module: $modelName,
                action: 'Delete Data',
                description: "Deleted {$modelName} '{$identifier}' (ID: {$model->id})"
            );
        });
    }
}
