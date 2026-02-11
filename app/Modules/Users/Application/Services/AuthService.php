<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Common\Services\ServiceResult;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Valida credenciales y estado del usuario.
     */
    public function login(array $credentials): ServiceResult
    {
        if (!Auth::guard('web')->attempt($credentials)) {
            return ServiceResult::fail('Credenciales incorrectas.', 401);
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            Auth::logout();
            return ServiceResult::fail('Usuario inactivo.', 403);
        }

        return ServiceResult::ok($user);
    }

    /**
     * Registro con asignación de rol automática.
     */
    public function register(array $data): ServiceResult
    {
        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        Auth::login($user);

        return ServiceResult::ok($user, 'Registro exitoso.');
    }
}
