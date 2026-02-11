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

    {{-- FILTROS --}}
    <form action="{{ route('logistics.index') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 mb-8">
        {{-- Rango de fechas --}}
        <div class="bg-white border border-gray-100 p-1.5 rounded-2xl shadow-sm flex items-center gap-2 px-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="text-[10px] font-bold border-none p-2 focus:ring-0">
            <span class="text-gray-300 font-bold">/</span>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="text-[10px] font-bold border-none p-2 focus:ring-0">
        </div>

        {{-- FILTRO DE ESTADO --}}
        <div class="bg-white border border-gray-100 p-1.5 rounded-2xl shadow-sm flex items-center pr-4">
            <select name="status" onchange="this.form.submit()" class="text-[10px] font-black border-none focus:ring-0 uppercase tracking-widest bg-transparent">
                <option value="">Todos los Estados</option>
                <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pendientes</option>
                <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Auditados</option>
                <option value="disapproved" {{ $filters['status'] === 'disapproved' ? 'selected' : '' }}>Desaprobados</option>
            </select>
        </div>

        {{-- Buscador --}}
        <div class="relative w-full md:w-64">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar conductor..." class="text-[10px] font-bold border-gray-100 rounded-2xl px-6 py-3.5 w-full shadow-sm">
        </div>

        <button type="submit" class="bg-gray-900 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest active:scale-95">Filtrar</button>
        <a href="{{ route('logistics.index') }}" class="bg-gray-100 text-gray-400 px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase flex items-center justify-center tracking-widest">Limpiar</a>
    </form>

    {{-- TABLA RECORRIDOS --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-12">
        <div class="px-8 py-6 bg-gray-900 flex justify-between items-center text-white">
            <h3 class="font-black text-base uppercase italic tracking-tighter">Recorridos</h3>
            <span class="text-[10px] font-black text-orange-400 uppercase italic font-bold">
                Periodo: {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/50 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-8 py-4 text-left">Conductor</th>
                        <th class="px-8 py-4 text-left">Ruta</th>
                        <th class="px-8 py-4 text-center">Distancia</th>
                        <th class="px-8 py-4 text-center">Estado</th>
                        <th class="px-8 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($completedToday as $trip)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5 text-xs font-black text-gray-800 uppercase leading-none">
                            {{ $trip->user->name }}
                            <span class="block text-[8px] text-gray-400 mt-1 italic font-bold">
                                {{ \Carbon\Carbon::parse($trip->end_time)->format('d/m/Y H:i') }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-[10px] font-bold text-gray-600 uppercase">
                            {{ $trip->origin }} → {{ $trip->destination }}
                        </td>
                        <td class="px-8 py-5 text-center font-black text-gray-900 bg-gray-50/50 italic text-[10px]">
                            {{ ($trip->end_odometer ?? 0) - $trip->start_odometer }} KM
                        </td>
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
                                <a href="{{ route('logistics.trip.show', $trip->id) }}" class="text-[9px] font-black text-gray-900 border-b-2 border-reversso uppercase italic mr-3">Detalle</a>

                                @if($trip->approved_by !== 0)
                                <form action="{{ route('logistics.disapprove', $trip->id) }}" method="POST" onsubmit="return confirm('¿Desaprobar este trayecto?')">
                                    @csrf
                                    <button type="submit" class="bg-red-500 text-white px-4 py-1.5 rounded-xl text-[9px] font-black uppercase shadow-md active:scale-95 hover:bg-red-600 transition-all">
                                        Desaprobar
                                    </button>
                                </form>

                                @if(!$trip->approved_at)
                                <form action="{{ route('logistics.approve', $trip->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-green-500 text-white px-4 py-1.5 rounded-xl text-[9px] font-black uppercase shadow-md active:scale-95 hover:bg-green-600 transition-all">
                                        Aprobar
                                    </button>
                                </form>
                                @endif
                                @else
                                <form action="{{ route('logistics.approve', $trip->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-gray-400 text-white px-4 py-1.5 rounded-xl text-[9px] font-black uppercase shadow-md">Re-Aprobar</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-10 text-center text-[10px] font-black uppercase text-gray-300 italic">No hay registros con los filtros seleccionados</td>
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

<style>
    .custom-pagination nav svg {
        width: 20px;
    }

    .custom-pagination nav span,
    .custom-pagination nav a {
        border-radius: 12px !important;
        font-weight: 900 !important;
        font-size: 10px !important;
        text-transform: uppercase !important;
        margin: 0 2px !important;
    }

    .custom-pagination nav .bg-indigo-600 {
        background-color: #f36f21 !important;
        border-color: #f36f21 !important;
    }
</style>
@endsection
