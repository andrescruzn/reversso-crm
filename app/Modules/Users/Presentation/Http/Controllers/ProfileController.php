<?php

namespace App\Modules\Users\Presentation\Http\Controllers;

use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class ProfileController
{
    public function show(): View
    {
        return view('modules.profile.index', [
            'user' => auth()->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required'         => 'La nueva contraseña es obligatoria.',
            'password.min'              => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'Las contraseñas no coinciden.',
        ]);

        $user = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => $validated['password']]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Tu contraseña ha sido actualizada.');
    }
}
