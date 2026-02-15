@extends('layouts.crm')

@section('module_title', 'Usuarios / Cambiar Password')

@section('content')
<div class="max-w-3xl mx-auto px-2 md:px-0">
    <div class="mb-8 md:mb-10">
        <a href="{{ route('users.edit', $editUser->id) }}" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-reversso transition-colors flex items-center gap-2 mb-2">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
            </svg>
            Volver
        </a>
        <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">
            Cambiar Password
        </h2>
        <p class="text-gray-500 font-bold italic uppercase text-[10px] md:text-xs tracking-widest mt-1">
            {{ $editUser->name }}
        </p>
    </div>

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-[10px] md:text-xs font-black uppercase tracking-widest italic">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white shadow-sm rounded-[35px] md:rounded-[40px] border border-gray-100 overflow-hidden">
        <form action="{{ route('users.password.update', $editUser->id) }}" method="POST" class="p-6 md:p-10 space-y-5 md:space-y-6">
            @csrf
            @method('PUT')

            {{-- INFO DEL USUARIO --}}
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="h-12 w-12 bg-orange-50 rounded-xl flex items-center justify-center text-reversso font-black text-sm uppercase flex-shrink-0">
                    {{ strtoupper(substr($editUser->name, 0, 2)) }}
                </div>
                <div>
                    <p class="font-bold text-sm text-gray-900">{{ $editUser->name }}</p>
                    <p class="text-[10px] text-gray-400 font-medium">{{ $editUser->email }}</p>
                </div>
            </div>

            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Nueva Password</label>
                <div class="relative">
                    <input type="password" name="password" required
                        class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 pr-12 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                        placeholder="Minimo 8 caracteres">
                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1">
                        <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 ml-2 text-[10px] font-bold text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Confirmar Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" required
                        class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 pr-12 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                        placeholder="Repetir password">
                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1">
                        <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>
            </div>

            <div class="pt-4 md:pt-6">
                <button type="submit" class="w-full bg-reversso text-white py-5 rounded-2xl font-black text-[11px] md:text-xs uppercase tracking-[0.2em] shadow-lg hover:bg-orange-600 active:scale-[0.98] transition-all">
                    Actualizar Password
                </button>
                <p class="text-center text-[9px] text-gray-400 font-bold uppercase mt-4 tracking-tighter italic">
                    El usuario debera iniciar sesion con la nueva contraseña.
                </p>
            </div>
        </form>
    </div>
</div>
@endsection
