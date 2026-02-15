<?php

declare(strict_types=1);

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Listado de usuarios con filtros.
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status');

        $query = User::with('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.name', $roleFilter));
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('name')->paginate(15)->appends($request->all());
        $roles = Role::all();

        return view('modules.users.index', [
            'user'         => auth()->user(),
            'users'        => $users,
            'roles'        => $roles,
            'search'       => $search,
            'roleFilter'   => $roleFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Formulario de creación.
     */
    public function create(Request $request): View
    {
        return view('modules.users.create', [
            'user'          => auth()->user(),
            'requestedRole' => $request->query('role'),
            'roles'         => Role::all(),
        ]);
    }

    /**
     * Guardar nuevo usuario.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role'     => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'],
                    'password'  => $validated['password'],
                    'is_active' => true,
                ]);

                $role = Role::where('name', $validated['role'])
                    ->orWhere('display_name', $validated['role'])
                    ->first();

                if (!$role) {
                    throw new \Exception("El rol '{$validated['role']}' no está configurado en el sistema.");
                }

                $user->assignRole($role);
            });

            return redirect()
                ->route('users.index')
                ->with('success', 'Usuario registrado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Fallo en el registro: ' . $e->getMessage());
        }
    }

    /**
     * Formulario de edición.
     */
    public function edit(int $id): View
    {
        $editUser = User::with('roles')->findOrFail($id);

        return view('modules.users.edit', [
            'user'     => auth()->user(),
            'editUser' => $editUser,
            'roles'    => Role::all(),
        ]);
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $editUser = User::findOrFail($id);

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role'  => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($editUser, $validated) {
                $editUser->update([
                    'name'  => $validated['name'],
                    'email' => $validated['email'],
                ]);

                $role = Role::where('name', $validated['role'])
                    ->orWhere('display_name', $validated['role'])
                    ->first();

                if (!$role) {
                    throw new \Exception("El rol '{$validated['role']}' no está configurado.");
                }

                $editUser->roles()->sync([$role->id]);
            });

            return redirect()
                ->route('users.index')
                ->with('success', "Usuario {$editUser->name} actualizado.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Toggle activo/inactivo.
     */
    public function toggleActive(int $id): RedirectResponse
    {
        $editUser = User::findOrFail($id);

        // No permitir desactivarse a sí mismo
        if ($editUser->id === auth()->id()) {
            return redirect()->back()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        $editUser->update(['is_active' => !$editUser->is_active]);

        $status = $editUser->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('users.index')
            ->with('success', "Usuario {$editUser->name} {$status}.");
    }

    /**
     * Formulario de cambiar contraseña.
     */
    public function editPassword(int $id): View
    {
        $editUser = User::findOrFail($id);

        return view('modules.users.change-password', [
            'user'     => auth()->user(),
            'editUser' => $editUser,
        ]);
    }

    /**
     * Actualizar contraseña.
     */
    public function updatePassword(Request $request, int $id): RedirectResponse
    {
        $editUser = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $editUser->update(['password' => $validated['password']]);

        return redirect()
            ->route('users.index')
            ->with('success', "Contraseña de {$editUser->name} actualizada.");
    }
}
