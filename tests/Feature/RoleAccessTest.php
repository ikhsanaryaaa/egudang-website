<?php

namespace Tests\Feature;

use App\Filament\Pages\EoqReport;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Reports;
use App\Filament\Resources\EoqCalculationResource;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\RoleResource;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_is_limited_to_dashboard_reports_categories_and_stock_history(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        $this->actingAs($manager);

        $this->assertSame([
            'export reports',
            'view categories',
            'view dashboard',
            'view reports',
            'view stock movements',
        ], $manager->getAllPermissions()->pluck('name')->sort()->values()->all());

        $this->assertTrue($manager->can('viewAny', Category::class));
        $this->assertFalse($manager->can('create', Category::class));
        $this->assertTrue($manager->can('viewAny', StockTransaction::class));
        $this->assertFalse($manager->can('create', StockTransaction::class));
        $this->assertFalse($manager->can('viewAny', Product::class));
        $this->assertFalse($manager->can('viewAny', User::class));
        $this->assertFalse($manager->can('viewAny', AuditLog::class));
        $this->assertTrue(Dashboard::canAccess());
        $this->assertTrue(Reports::canAccess());
        $this->assertTrue(EoqReport::canAccess());
        $this->assertFalse(EoqCalculationResource::canAccess());
        $this->assertFalse(RoleResource::canAccess());
    }

    public function test_super_admin_has_every_seeded_permission_and_all_resource_access(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $this->actingAs($superAdmin);

        $this->assertSame(
            \Spatie\Permission\Models\Permission::count(),
            $superAdmin->getAllPermissions()->count(),
        );

        $this->assertTrue($superAdmin->can('create', Product::class));
        $this->assertTrue($superAdmin->can('create', Category::class));
        $this->assertTrue($superAdmin->can('create', User::class));
        $this->assertTrue($superAdmin->can('create', StockTransaction::class));
        $this->assertTrue($superAdmin->can('viewAny', AuditLog::class));
        $this->assertTrue(Reports::canAccess());
        $this->assertTrue(EoqReport::canAccess());
        $this->assertTrue(EoqCalculationResource::canAccess());
        $this->assertTrue(RoleResource::canAccess());

        $superAdminRole = Role::findByName('Super Admin');
        $this->assertFalse(RoleResource::canEdit($superAdminRole));
        $this->assertFalse(RoleResource::canDelete($superAdminRole));
    }

    public function test_super_admin_can_build_a_custom_role_from_permissions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customRole = Role::create(['name' => 'Auditor']);
        $customRole->syncPermissions([
            'view audit logs',
        ]);

        $auditor = User::factory()->create();
        $auditor->assignRole($customRole);
        $this->actingAs($auditor);

        $this->assertTrue($auditor->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse(Dashboard::canAccess());
        $this->assertTrue($auditor->can('viewAny', AuditLog::class));
        $this->assertFalse($auditor->can('viewAny', Product::class));
        $this->assertFalse(Reports::canAccess());
        $this->assertFalse(EoqCalculationResource::canAccess());
        $this->assertFalse(RoleResource::canAccess());
        $this->assertSame(
            AuditLogResource::getUrl(),
            app(LoginResponseContract::class)->toResponse(request())->getTargetUrl(),
        );

        $customRole->syncPermissions([
            'view reports',
            'export reports',
        ]);

        $this->assertFalse($auditor->can('viewAny', AuditLog::class));
        $this->assertTrue(Reports::canAccess());
        $this->assertTrue($auditor->can('export reports'));
    }
}
