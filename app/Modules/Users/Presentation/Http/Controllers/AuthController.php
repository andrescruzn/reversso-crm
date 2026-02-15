<?php

declare(strict_types=1);

namespace App\Modules\Users\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Application\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($credentials, $request->boolean('remember'));

        if ($result->isFailure()) {
            return back()->withErrors(['email' => $result->message])->withInput();
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function showLogin()
    {
        return Auth::check() ? redirect()->route('dashboard') : view('auth.login');
    }

    public function logout(Request $request)
    {
        // 1. Cierra la sesión en el guard de la Web
        Auth::guard('web')->logout();

        // 2. Invalida la sesión del usuario para borrar los datos
        $request->session()->invalidate();

        // 3. Regenera el token CSRF para el próximo usuario
        $request->session()->regenerateToken();

        // 4. Redirige al login con un mensaje de éxito
        return redirect('/login')->with('status', 'Sesión cerrada correctamente.');
    }
}
