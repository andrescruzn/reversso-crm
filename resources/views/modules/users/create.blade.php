@extends('layouts.crm')

@section('module_title', 'Usuarios / Nuevo Registro')

@section('content')
<div class="max-w-3xl mx-auto px-2 md:px-0">
    {{-- CABECERA ADAPTATIVA --}}
    <div class="mb-8 md:mb-10">
        <a href="{{ route('logistics.index') }}" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-reversso transition-colors flex items-center gap-2 mb-2">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
            </svg>
            Volver
        </a>
        <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">
            Nuevo {{ $requestedRole === 'conductor' ? 'Conductor' : 'Usuario' }}
        </h2>
    </div>

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-[10px] md:text-xs font-black uppercase tracking-widest italic">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white shadow-sm rounded-[35px] md:rounded-[40px] border border-gray-100 overflow-hidden">
        <form action="{{ route('users.store') }}" method="POST" class="p-6 md:p-10 space-y-5 md:space-y-6">
            @csrf

            {{-- Campo: Nombre --}}
            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                    placeholder="Ej: Juan Pérez">
            </div>

            {{-- Campo: Email --}}
            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Email Corporativo</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                    placeholder="juan.perez@reversso.com"
                    inputmode="email">
            </div>

            {{-- Campo: Password --}}
            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Password Temporal</label>
                <input type="password" name="password" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                    placeholder="••••••••">
            </div>

            {{-- Gestión de Rol --}}
            @if($requestedRole)
            <input type="hidden" name="role" value="{{ $requestedRole }}">
            <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100 flex items-center gap-3">
                <div class="w-2 h-2 bg-reversso rounded-full animate-pulse"></div>
                <div class="text-[10px] font-black text-orange-700 uppercase tracking-widest italic">
                    Asignando rol: <span class="text-orange-900 underline">{{ $requestedRole }}</span>
                </div>
            </div>
            @else
            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Rol del Sistema</label>
                <select name="role" class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all appearance-none">
                    @foreach($roles as $role)
                    <option value="{{ $role->display_name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Botón de Acción --}}
            <div class="pt-4 md:pt-6">
                <button type="submit" class="w-full bg-reversso text-white py-5 rounded-2xl font-black text-[11px] md:text-xs uppercase tracking-[0.2em] shadow-lg hover:bg-orange-600 active:scale-[0.98] transition-all">
                    Finalizar Registro
                </button>
                <p class="text-center text-[9px] text-gray-400 font-bold uppercase mt-4 tracking-tighter italic">
                    El usuario podrá cambiar su contraseña al iniciar sesión por primera vez.
                </p>
            </div>
        </form>
    </div>
</div>
@endsection
