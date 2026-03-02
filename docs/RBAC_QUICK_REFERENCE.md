# RBAC Quick Reference

## Common Commands

```bash
# Clear all RBAC caches
php artisan rbac:clear-cache

# Sync users from legacy role column
php artisan rbac:sync-users

# Force sync all users
php artisan rbac:sync-users --force

# Check RBAC system health
php artisan rbac:health

# Detailed health check
php artisan rbac:health --detailed

# Reset Spatie permission cache only
php artisan permission:cache-reset

# Run RBAC seeder
php artisan db:seed --class=RbacSeeder
```

---

## Permission Checking

### In PHP/Controllers

```php
// Single permission
if ($user->hasPermission('users.create')) { }

// Multiple permissions (OR)
if ($user->hasAnyPermission(['users.edit', 'users.delete'])) { }

// Check role
if ($user->hasRole('admin')) { }

// Multiple roles (OR)
if ($user->hasAnyRole(['admin', 'hr'])) { }
```

### In Routes

```php
// Permission middleware
Route::middleware('permission:users.view')->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
});

// Role middleware
Route::middleware('role:admin,hr')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
});
```

### In Blade

```blade
@can('users.view.all')
    <a href="{{ route('admin.users.index') }}">Manage Users</a>
@endcan

@role('admin')
    <button>Admin Action</button>
@endrole

@hasanyrole('hr|admin')
    <div>HR Content</div>
@endhasanyrole
```

---

## Role-Permission Matrix

| Permission | Super Admin | Admin | HR | Finance | Employee |
|------------|-------------|-------|----|---------| ---------|
| **Users** |
| users.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| users.view.all | ✓ | ✓ | ✓ | - | - |
| users.create | ✓ | ✓ | - | - | - |
| users.edit | ✓ | ✓ | - | - | - |
| users.delete | ✓ | ✓ | - | - | - |
| users.manage_roles | ✓ | - | - | - | - |
| **Attendance** |
| attendance.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| attendance.view.all | ✓ | ✓ | ✓ | - | - |
| attendance.view.department | ✓ | ✓ | ✓ | ✓ | - |
| attendance.create | ✓ | ✓ | ✓ | - | ✓ |
| attendance.edit | ✓ | ✓ | ✓ | - | - |
| attendance.approve | ✓ | ✓ | ✓ | - | - |
| attendance.lock.month | ✓ | ✓ | ✓ | - | - |
| attendance.unlock.month | ✓ | ✓ | - | - | - |
| **Leave** |
| leave.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| leave.view.all | ✓ | ✓ | ✓ | - | - |
| leave.apply | ✓ | ✓ | ✓ | - | ✓ |
| leave.approve | ✓ | ✓ | ✓ | - | - |
| leave.reject | ✓ | ✓ | ✓ | - | - |
| **Payroll** |
| payroll.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| payroll.view.all | ✓ | ✓ | ✓ | ✓ | - |
| payroll.generate | ✓ | ✓ | ✓ | ✓ | - |
| payroll.approve | ✓ | ✓ | - | ✓ | - |
| payroll.pay | ✓ | ✓ | - | ✓ | - |
| payroll.manage_structure | ✓ | ✓ | ✓ | ✓ | - |
| **Settings** |
| settings.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| settings.edit | ✓ | ✓ | - | - | - |
| settings.smtp | ✓ | - | - | - | - |
| settings.company | ✓ | ✓ | - | - | - |

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Infinite loop / timeout | 1. Remove `Gate::before()` from AuthServiceProvider<br>2. `php artisan rbac:clear-cache`<br>3. Check re-entry guards |
| Permission always false | 1. `php artisan rbac:sync-users --force`<br>2. `php artisan rbac:clear-cache`<br>3. Verify role assignment |
| Slow performance | 1. Check cache driver is NOT database<br>2. Switch to Redis cache<br>3. Run `php artisan optimize` |
| Permission not found | 1. `php artisan db:seed --class=RbacSeeder`<br>2. `php artisan permission:cache-reset` |

---

## Performance Targets

| Metric | Target | How to Check |
|--------|--------|--------------|
| Page load | < 500ms | Browser DevTools Network tab |
| Permission check | < 5ms | `php artisan test --filter=Performance` |
| Database queries | < 20/page | Laravel Debugbar or Telescope |
| Cache driver | file/redis | `php artisan rbac:health` |

---

## Adding New Permission

```php
// 1. Edit database/seeders/RbacSeeder.php
protected function getAllPermissions(): array
{
    return [
        // ... existing permissions
        'module.new_action',
    ];
}

// 2. Assign to roles
protected function getRolePermissionMapping(): array
{
    return [
        UserRole::ADMIN->value => [
            // ... existing permissions
            'module.new_action',
        ],
    ];
}

// 3. Re-run seeder
php artisan db:seed --class=RbacSeeder
```

---

## Migration Checklist

- [ ] Backup database
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed RBAC: `php artisan db:seed --class=RbacSeeder`
- [ ] Sync users: `php artisan rbac:sync-users`
- [ ] Health check: `php artisan rbac:health`
- [ ] Test login for each role
- [ ] Verify permissions work
- [ ] Clear caches: `php artisan rbac:clear-cache`
- [ ] Monitor performance
- [ ] Check logs for errors

---

## Configuration Files

| File | Purpose |
|------|---------|
| `config/permission.php` | Spatie configuration |
| `app/Models/User.php` | HasRoles trait, backward compatibility |
| `app/Support/PermissionScopeResolver.php` | Scope resolution logic |
| `app/Policies/*` | Authorization policies |
| `database/seeders/RbacSeeder.php` | Roles & permissions seeding |

---

## Important: Cache Driver

**NEVER use database cache driver!**

```env
# ❌ BAD - causes infinite loops
CACHE_STORE=database

# ✅ GOOD - for development/small teams
CACHE_STORE=file

# ✅ BETTER - for production/large teams
CACHE_STORE=redis
```

Verify: `php artisan rbac:health`
