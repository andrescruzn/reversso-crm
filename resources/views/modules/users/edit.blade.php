@extends('layouts.crm')

@section('module_title', 'Usuarios / Editar')

@section('content')
<div class="max-w-3xl mx-auto px-2 md:px-0">
    <div class="mb-8 md:mb-10">
        <a href="{{ route('users.index') }}" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-reversso transition-colors flex items-center gap-2 mb-2">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
            </svg>
            Volver
        </a>
        <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">
            Editar Usuario
        </h2>
    </div>

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-[10px] md:text-xs font-black uppercase tracking-widest italic">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white shadow-sm rounded-[35px] md:rounded-[40px] border border-gray-100 overflow-hidden">
        <form action="{{ route('users.update', $editUser->id) }}" method="POST" class="p-6 md:p-10 space-y-5 md:space-y-6">
            @csrf
            @method('PUT')

            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name', $editUser->name) }}" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all">
                @error('name')
                    <p class="mt-1 ml-2 text-[10px] font-bold text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Email</label>
                <input type="email" name="email" value="{{ old('email', $editUser->email) }}" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all"
                    inputmode="email">
                @error('email')
                    <p class="mt-1 ml-2 text-[10px] font-bold text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="group">
                <label class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-2 transition-colors group-focus-within:text-reversso">Rol del Sistema</label>
                <select name="role" class="w-full bg-gray-50 border-none rounded-2xl px-5 md:px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all appearance-none">
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ $editUser->roles->first()?->id === $role->id ? 'selected' : '' }}>
                        {{ $role->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <div class="flex items-center gap-2">
                    @if($editUser->is_active)
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-[10px] font-black text-green-700 uppercase tracking-widest">Activo</span>
                    @else
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        <span class="text-[10px] font-black text-red-700 uppercase tracking-widest">Inactivo</span>
                    @endif
                </div>
                @if($editUser->id !== auth()->id())
                <form action="{{ route('users.toggle-active', $editUser->id) }}" method="POST" class="ml-auto">
                    @csrf
                    <button type="submit" class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border transition-all
                        {{ $editUser->is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}">
                        {{ $editUser->is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </form>
                @endif
            </div>

            <div class="pt-4 md:pt-6">
                <button type="submit" class="w-full bg-reversso text-white py-5 rounded-2xl font-black text-[11px] md:text-xs uppercase tracking-[0.2em] shadow-lg hover:bg-orange-600 active:scale-[0.98] transition-all">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    {{-- ACCESO RAPIDO A PASSWORD --}}
    <div class="mt-6">
        <a href="{{ route('users.password.edit', $editUser->id) }}"
           class="flex items-center gap-3 p-5 bg-white rounded-[30px] border border-gray-100 shadow-sm hover:border-reversso hover:shadow-md transition-all group">
            <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-reversso group-hover:bg-orange-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-black text-gray-900 group-hover:text-reversso transition-colors">Cambiar Password</p>
                <p class="text-[10px] text-gray-400 font-bold">Asignar nueva contraseña a este usuario</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
