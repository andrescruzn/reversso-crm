@extends('layouts.crm')

@section('module_title', 'Supervisión Logística')

@section('content')
<div class="max-w-7xl mx-auto px-2 md:px-0">

    {{-- MÉTRICAS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10">
        <div class="bg-white p-6 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">En Ruta</p>
            <p class="text-4xl font-black text-gray-900 mt-2">{{ $totalActive }}</p>
        </div>
        <div class="bg-white p-6 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Por Auditar</p>
            <p class="text-4xl font-black text-reversso mt-2">{{ $pendingApproval }}</p>
        </div>
        <div class="bg-white p-6 rounded-[30px] border-l-8 border-l-reversso shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">En Planta Hoy</p>
            <p class="text-4xl font-black text-gray-900 mt-2 italic">{{ $attendanceToday }}</p>
        </div>
        <div class="bg-white p-6 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Total Conductores</p>
            <p class="text-4xl font-black text-gray-200 mt-2 italic">{{ $drivers->count() }}</p>
        </div>
    </div>

    {{-- FILTROS Y EXPORTAR --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
        <form action="{{ route('logistics.index') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full lg:w-auto">
            <div class="bg-white border border-gray-100 p-1.5 rounded-2xl shadow-sm flex items-center gap-2 px-3">
                <input type="date" name="from" value="{{ $filters['from'] }}" class="text-[10px] font-bold border-none p-2 focus:ring-0">
                <span class="text-gray-300 font-bold">/</span>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="text-[10px] font-bold border-none p-2 focus:ring-0">
            </div>

            <div class="bg-white border border-gray-100 p-1.5 rounded-2xl shadow-sm flex items-center pr-4">
                <select name="status" onchange="this.form.submit()" class="text-[10px] font-black border-none focus:ring-0 uppercase tracking-widest bg-transparent">
                    <option value="">Todos los Estados</option>
                    <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Auditados</option>
                    <option value="disapproved" {{ $filters['status'] === 'disapproved' ? 'selected' : '' }}>Desaprobados</option>
                </select>
            </div>

            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar conductor..." class="text-[10px] font-bold border-gray-100 rounded-2xl px-6 py-3.5 w-full shadow-sm">
            </div>

            <button type="submit" class="bg-gray-900 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest active:scale-95">Filtrar</button>
            <a href="{{ route('logistics.index') }}" class="bg-gray-100 text-gray-400 px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase flex items-center justify-center tracking-widest">Limpiar</a>
        </form>

        <a href="{{ route('logistics.export.tracking', request()->all()) }}" class="bg-reversso text-white px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg flex items-center gap-2 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Exportar CSV
        </a>
    </div>

    {{-- TABLA RUTA --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-8">
        <div class="px-8 py-5 bg-reversso flex justify-between items-center text-white">
            <h3 class="font-black text-sm uppercase italic tracking-tighter text-white">Conductores en Ruta</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <tbody class="divide-y divide-gray-50">
                @forelse($driversInRoute as $active)
                <tr>
                    <td class="px-8 py-5 text-xs font-black text-gray-800 uppercase italic">{{ $active->user->name }}</td>
                    <td class="px-8 py-5 text-[10px] font-bold text-gray-500 uppercase">{{ $active->origin }} → {{ $active->destination }}</td>
                    <td class="px-8 py-5 text-right"><span class="text-[9px] font-black text-reversso uppercase italic tracking-widest">Viaje Activo</span></td>
                </tr>
                @empty
                <tr>
                    <td class="px-8 py-8 text-center text-[10px] font-black text-gray-300 italic uppercase">No hay conductores en ruta</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TABLA PLANTA --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-8">
        <div class="px-8 py-5 bg-gray-100 flex justify-between items-center text-gray-500">
            <h3 class="font-black text-sm uppercase italic tracking-tighter">Personal en Planta</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <tbody class="divide-y divide-gray-50">
                @forelse($onlyAttendance as $plant)
                <tr>
                    <td class="px-8 py-4 text-xs font-black text-gray-600 uppercase">{{ $plant->user_name }}</td>
                    <td class="px-8 py-4 text-[10px] font-bold text-gray-400 uppercase italic leading-tight">Entrada: {{ \Carbon\Carbon::parse($plant->start_time)->format('H:i') }}</td>
                    <td class="px-8 py-4 text-right"><span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest italic">En Planta</span></td>
                </tr>
                @empty
                <tr>
                    <td class="px-8 py-8 text-center text-[10px] font-black text-gray-300 italic uppercase">No hay personal adicional</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TABLA HISTORIAL --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-12">
        <div class="px-8 py-6 bg-gray-900 flex justify-between items-center text-white">
            <h3 class="font-black text-base uppercase italic tracking-tighter text-white">Historial de Recorridos</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/50 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-8 py-4 text-left">Conductor</th>
                        <th class="px-8 py-4 text-left">Ruta</th>
                        <th class="px-8 py-4 text-center">Estado</th>
                        <th class="px-8 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($completedToday as $trip)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5 text-xs font-black text-gray-800 uppercase italic">
                            {{ $trip->user->name }}
                            <span class="block text-[8px] text-gray-400 mt-1 italic font-bold">{{ \Carbon\Carbon::parse($trip->end_time)->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-8 py-5 text-[10px] font-bold text-gray-600 uppercase">{{ $trip->origin }} → {{ $trip->destination }}</td>
                        <td class="px-8 py-5 text-center">
                            @if($trip->approved_by === 0)
                            <span class="text-[8px] font-black text-red-600 bg-red-50 px-2 py-1 rounded-md uppercase border border-red-100">Desaprobado</span>
                            @elseif($trip->approved_at)
                            <span class="text-[8px] font-black text-green-600 bg-green-50 px-2 py-1 rounded-md uppercase border border-green-100">Auditado</span>
                            @else
                            <span class="text-[8px] font-black text-orange-500 bg-orange-50 px-2 py-1 rounded-md uppercase border border-orange-100">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex justify-end items-center gap-2">
                                @php $currentPage = $completedToday->currentPage(); @endphp

                                {{-- BOTÓN DESAPROBAR --}}
                                @if($trip->approved_by !== 0)
                                <form action="{{ route('logistics.disapprove', $trip->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="from" value="{{ $filters['from'] }}">
                                    <input type="hidden" name="to" value="{{ $filters['to'] }}">
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <input type="hidden" name="trips_page" value="{{ $currentPage }}">
                                    <button type="submit" class="bg-red-500 text-white px-4 py-1.5 rounded-xl text-[9px] font-black uppercase">Desaprobar</button>
                                </form>
                                @endif

                                {{-- BOTÓN APROBAR --}}
                                @if(!$trip->approved_at || $trip->approved_by === 0)
                                <form action="{{ route('logistics.approve', $trip->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="from" value="{{ $filters['from'] }}">
                                    <input type="hidden" name="to" value="{{ $filters['to'] }}">
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <input type="hidden" name="trips_page" value="{{ $currentPage }}">
                                    <button type="submit" class="bg-green-500 text-white px-4 py-1.5 rounded-xl text-[9px] font-black uppercase">Aprobar</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-10 text-center text-[10px] font-black text-gray-300 italic uppercase">No hay registros</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($completedToday->hasPages())
        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 custom-pagination">{{ $completedToday->links() }}</div>
        @endif
    </div>
</div>
@endsection
