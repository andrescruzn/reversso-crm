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

/**
 * UserController
 * Maneja la persistencia de usuarios asegurando la integridad del RBAC.
 */
class UserController extends Controller
{
    /**
     * Formulario de creación con soporte para roles predefinidos.
     */
    public function create(Request $request): View
    {
        return view('modules.users.create', [
            'user'          => auth()->user(),
            // 'requestedRole' se usará en la vista para mostrar "Conductor" si viene "driver"
            'requestedRole' => $request->query('role'),
            'roles'         => Role::all()
        ]);
    }

    /**
     * Proceso de guardado con lógica de dominio y transacción.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role'     => 'required|string'
        ]);

        try {
            $user = DB::transaction(function () use ($validated) {
                // 1. Crear usuario (el cast 'hashed' en el User model hace el Hash::make)
                $user = User::create([
                    'name'      => $validated['name'],
                    'email'     => $validated['email'],
                    'password'  => $validated['password'],
                    'is_active' => true
                ]);

                // 2. Mapeo de rol: Si el form envía 'driver', buscamos 'Conductor'
                $roleDisplayName = ($validated['role'] === 'driver') ? 'Conductor' : $validated['role'];

                $role = Role::where('display_name', $roleDisplayName)->first();

                if (!$role) {
                    throw new \Exception("El rol '{$roleDisplayName}' no está configurado en el sistema.");
                }

                // 3. Asignación mediante lógica de dominio (syncWithoutDetaching)
                $user->assignRole($role);

                return $user;
            });

            return redirect()
                ->route('logistics.index')
                ->with('success', "Usuario {$user->name} registrado correctamente.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Fallo en el registro: ' . $e->getMessage());
        }
    }
}
