@extends('layouts.crm')

@section('module_title', 'Usuarios')

@section('content')
<div class="max-w-5xl mx-auto px-2 md:px-0">

    {{-- CABECERA --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8 md:mb-10 gap-4">
        <div>
            <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">
                Usuarios
            </h2>
            <p class="text-gray-500 font-bold italic uppercase text-[10px] md:text-xs tracking-widest mt-1">
                Gestionar cuentas del sistema
            </p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 bg-reversso text-white px-6 py-3 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-orange-600 active:scale-[0.98] transition-all shadow-lg self-start md:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Usuario
        </a>
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('users.index') }}" class="mb-6">
        <div class="bg-white p-4 md:p-6 rounded-[30px] border border-gray-100 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-4">
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-2">Buscar</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nombre o email..."
                           class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-2">Rol</label>
                    <select name="role" class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all appearance-none">
                        <option value="">Todos</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ $roleFilter === $role->name ? 'selected' : '' }}>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-2">Estado</label>
                    <select name="status" class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-reversso transition-all appearance-none">
                        <option value="">Todos</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Activos</option>
                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-black active:scale-[0.98] transition-all">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLA --}}
    <div class="bg-white rounded-[30px] md:rounded-[35px] border border-gray-100 shadow-sm overflow-hidden">

        {{-- DESKTOP --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">Nombre</th>
                        <th class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">Email</th>
                        <th class="px-6 py-4 text-left text-[9px] font-black text-gray-400 uppercase tracking-widest">Rol</th>
                        <th class="px-6 py-4 text-center text-[9px] font-black text-gray-400 uppercase tracking-widest">Estado</th>
                        <th class="px-6 py-4 text-center text-[9px] font-black text-gray-400 uppercase tracking-widest">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 bg-orange-50 rounded-xl flex items-center justify-center text-reversso font-black text-xs uppercase flex-shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <span class="font-bold text-sm text-gray-900">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 font-medium">{{ $u->email }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest
                                {{ $u->roles->first()?->name === 'admin' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ $u->roles->first()?->display_name ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($u->is_active)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-50 text-red-700 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('users.edit', $u->id) }}" title="Editar"
                                   class="p-2 rounded-xl bg-gray-50 hover:bg-orange-50 text-gray-400 hover:text-reversso transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('users.password.edit', $u->id) }}" title="Cambiar password"
                                   class="p-2 rounded-xl bg-gray-50 hover:bg-orange-50 text-gray-400 hover:text-reversso transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </a>
                                @if($u->id !== auth()->id())
                                <form action="{{ route('users.toggle-active', $u->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $u->is_active ? 'Desactivar' : 'Activar' }}"
                                            class="p-2 rounded-xl bg-gray-50 hover:bg-{{ $u->is_active ? 'red' : 'green' }}-50 text-gray-400 hover:text-{{ $u->is_active ? 'red' : 'green' }}-600 transition-all">
                                        @if($u->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <p class="text-gray-400 font-bold text-sm italic">No se encontraron usuarios.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="md:hidden divide-y divide-gray-50">
            @forelse($users as $u)
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-orange-50 rounded-xl flex items-center justify-center text-reversso font-black text-xs uppercase flex-shrink-0">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-bold text-sm text-gray-900">{{ $u->name }}</p>
                            <p class="text-[10px] text-gray-400 font-medium">{{ $u->email }}</p>
                        </div>
                    </div>
                    @if($u->is_active)
                        <span class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></span>
                    @else
                        <span class="w-2 h-2 bg-red-500 rounded-full flex-shrink-0"></span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest
                        {{ $u->roles->first()?->name === 'admin' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                        {{ $u->roles->first()?->display_name ?? 'Sin rol' }}
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('users.edit', $u->id) }}" class="p-2 rounded-xl bg-gray-50 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <a href="{{ route('users.password.edit', $u->id) }}" class="p-2 rounded-xl bg-gray-50 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </a>
                        @if($u->id !== auth()->id())
                        <form action="{{ route('users.toggle-active', $u->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-xl bg-gray-50 text-gray-400">
                                @if($u->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @endif
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <p class="text-gray-400 font-bold text-sm italic">No se encontraron usuarios.</p>
            </div>
            @endforelse
        </div>

        {{-- PAGINACION --}}
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
