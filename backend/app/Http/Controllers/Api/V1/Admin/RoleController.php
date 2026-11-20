<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\PasswordRule;
use App\Support\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get();

        return ApiResponse::success([
            'roles' => $roles->map(fn (Role $role) => $this->presentRole($role))->values(),
            'permissions' => $permissions->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'group' => $permission->group,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:180'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::query()->create([
            'name' => Text::clean($data['name']),
            'slug' => $this->slug($data['name']),
            'description' => Text::clean($data['description'] ?? null),
            'is_super' => false,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);
        ActivityLog::write($request->user()->id, 'role.created', $role);

        return ApiResponse::success($this->presentRole($role->load('permissions')), 'Role created.', 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->is_super) {
            return ApiResponse::error('The Super Admin role cannot be edited.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:180'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => Text::clean($data['name']),
            'slug' => $this->slug($data['name'], $role->id),
            'description' => Text::clean($data['description'] ?? null),
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);
        ActivityLog::write($request->user()->id, 'role.updated', $role);

        return ApiResponse::success($this->presentRole($role->load('permissions')), 'Role updated.');
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_super) {
            return ApiResponse::error('The Super Admin role cannot be deleted.', 403);
        }

        if ($role->users()->exists()) {
            return ApiResponse::error('Reassign staff before deleting this role.', 422);
        }

        $role->delete();

        return ApiResponse::success(null, 'Role deleted.');
    }

    public function staff(): JsonResponse
    {
        $staff = User::query()->where('is_staff', true)->with('roles')->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get(['id', 'name', 'slug', 'is_super']);

        return ApiResponse::success([
            'staff' => $staff->map(fn (User $user) => $user->toStaffArray())->values(),
            'roles' => $roles,
        ]);
    }

    public function storeStaff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::rule()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        if ($denied = $this->guardSuperAssignment($request, $data['roles'])) {
            return $denied;
        }

        $user = User::query()->create([
            'name' => Text::clean($data['name']),
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'is_staff' => true,
            'email_verified_at' => now(),
        ]);
        $user->roles()->sync($data['roles']);

        return ApiResponse::success($user->toStaffArray(), 'Staff account created.', 201);
    }

    public function updateStaff(Request $request, User $user): JsonResponse
    {
        if (! $user->is_staff) {
            return ApiResponse::error('Staff member not found.', 404);
        }

        if ($user->isSuperAdmin()) {
            return ApiResponse::error('Super Admin accounts cannot be edited.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        if ($denied = $this->guardSuperAssignment($request, $data['roles'])) {
            return $denied;
        }

        $user->name = Text::clean($data['name']);
        $user->save();
        $user->roles()->sync($data['roles']);

        return ApiResponse::success($user->fresh()->toStaffArray(), 'Staff account updated.');
    }

    public function destroyStaff(Request $request, User $user): JsonResponse
    {
        if (! $user->is_staff) {
            return ApiResponse::error('Staff member not found.', 404);
        }

        if ($user->isSuperAdmin()) {
            return ApiResponse::error('Super Admin accounts cannot be deleted.', 403);
        }

        if ($user->id === $request->user()->id) {
            return ApiResponse::error('You cannot delete your own account.', 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return ApiResponse::success(null, 'Staff account deleted.');
    }

    private function guardSuperAssignment(Request $request, array $roleIds): ?JsonResponse
    {
        $assignsSuper = Role::query()->whereIn('id', $roleIds)->where('is_super', true)->exists();

        if ($assignsSuper && ! $request->user()->isSuperAdmin()) {
            return ApiResponse::error('Only a Super Admin can assign the Super Admin role.', 403);
        }

        return null;
    }

    private function presentRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_super' => $role->is_super,
            'permissions' => $role->permissions->pluck('id')->values(),
            'permission_slugs' => $role->permissions->pluck('slug')->values(),
        ];
    }

    private function slug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(Text::clean($name)) ?: 'role';
        $slug = $base;
        $i = 1;
        while (Role::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
