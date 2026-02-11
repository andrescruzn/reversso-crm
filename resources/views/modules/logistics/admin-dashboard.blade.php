@extends('layouts.crm')

@section('module_title', 'Logística / Panel Administrativo')

@section('content')
<div class="max-w-7xl mx-auto px-2 md:px-0">

    {{-- NOTIFICACIONES --}}
    <div class="space-y-4 mb-6">
        @if(session('success') || session('status'))
        <div class="p-4 bg-green-50 border-l-4 border-green-500 rounded-2xl flex items-center justify-between shadow-sm">
            <div class="flex items-center">
                <div class="bg-green-500 p-1 rounded-full mr-3 flex-shrink-0">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="text-[10px] md:text-[11px] font-black text-green-800 uppercase tracking-widest italic">{{ session('success') ?? session('status') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-400 hover:text-green-600 ml-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg></button>
        </div>
        @endif
    </div>

    {{-- CABECERA --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 md:mb-10 gap-4">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">Supervisión Logística</h2>
            <p class="text-[11px] md:text-sm text-gray-500 mt-2 font-medium italic">Panel de control de flota en tiempo real.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('users.create', ['role' => 'conductor']) }}" class="w-full sm:w-auto text-center bg-reversso text-white px-6 py-3.5 rounded-2xl text-[10px] font-black shadow-lg hover:bg-orange-600 transition-all uppercase tracking-widest active:scale-95">Nuevo Conductor</a>
        </div>
    </div>

    {{-- MÉTRICAS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10">
        <div class="bg-white p-6 md:p-8 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">En Ruta</p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 mt-2">{{ $totalActive }}</p>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Por Aprobar</p>
            <p class="text-4xl md:text-5xl font-black text-reversso mt-2">{{ $pendingApproval }}</p>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-[30px] border-l-8 border-l-reversso shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Conductores Hoy</p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 mt-2 italic">{{ $attendanceToday }}</p>
        </div>
        <div class="bg-white p-6 md:p-8 rounded-[30px] border border-gray-100 shadow-sm text-center">
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Alertas</p>
            <p class="text-4xl md:text-5xl font-black {{ $pendingApproval > 0 ? 'text-red-600' : 'text-gray-300' }} mt-2 italic">{{ $pendingApproval }}</p>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="mb-8 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
        <form action="{{ route('logistics.index') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
            <div class="bg-white border border-gray-100 p-1.5 rounded-2xl shadow-sm flex flex-col md:flex-row items-center gap-2">
                <div class="w-full md:w-auto flex items-center px-3 border-b md:border-b-0 md:border-r border-gray-100 pb-2 md:pb-0">
                    <span class="text-[8px] font-black text-gray-400 uppercase mr-2">Desde:</span>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="w-full text-[10px] font-bold border-none p-0 focus:ring-0">
                </div>
                <div class="w-full md:w-auto flex items-center px-3 pt-2 md:pt-0">
                    <span class="text-[8px] font-black text-gray-400 uppercase mr-2">Hasta:</span>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="w-full text-[10px] font-bold border-none p-0 focus:ring-0">
                </div>
            </div>

            <div class="relative w-full md:w-64">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar conductor..." class="text-[10px] font-bold border-gray-100 rounded-2xl px-6 py-3.5 focus:ring-reversso w-full shadow-sm pr-10">
                @if($search)
                <a href="{{ route('logistics.index', request()->except('search')) }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-reversso transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                    </svg></a>
                @endif
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-900 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-md active:scale-95">Filtrar</button>
                @if(request()->has('from') || request()->has('to') || request()->has('search'))
                <a href="{{ route('logistics.index') }}" class="bg-gray-100 text-gray-500 px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center">Limpiar</a>
                @endif
            </div>
        </form>

        <a href="{{ route('logistics.export.tracking', ['from' => $filters['from'], 'to' => $filters['to']]) }}" class="bg-gray-900 text-white px-6 py-4 rounded-2xl hover:bg-black shadow-lg flex items-center justify-center gap-2 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span class="text-[10px] font-black uppercase tracking-widest">Exportar Tracking</span>
        </a>
    </div>

    {{-- BLOQUE 1: ESTADO DE LA FLOTA (EN VIVO - SIEMPRE VISIBLE) --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-12">
        <div class="px-8 py-6 border-b border-gray-50 bg-gray-50/30 font-black text-gray-900 uppercase italic">Estado de la Flota (En Vivo)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <tbody class="divide-y divide-gray-50">
                    @forelse($driversInRoute as $tracking)
                    <tr class="hover:bg-orange-50/30">
                        <td class="px-8 py-5">
                            <div class="text-xs font-black text-gray-900 uppercase">{{ $tracking->user->name }}</div>
                            <div class="text-[8px] text-reversso font-bold uppercase mt-1 italic">En Ruta: {{ $tracking->destination }}</div>
                        </td>
                        <td class="px-8 py-5 text-center"><span class="bg-green-100 px-3 py-1 rounded-full text-[9px] font-black text-green-700 uppercase italic">En Ruta</span></td>
                        <td class="px-8 py-5 text-center text-[10px] font-black text-gray-700 italic">{{ \Carbon\Carbon::parse($tracking->start_time)->format('h:i A') }}</td>
                        <td class="px-8 py-5 text-right"><a href="{{ route('logistics.trip.show', $tracking->id) }}" class="text-[9px] font-black text-reversso border border-orange-200 px-4 py-2 rounded-xl uppercase">Detalles</a></td>
                    </tr>
                    @empty
                    @endforelse

                    @forelse($onlyAttendance as $waiting)
                    <tr class="bg-gray-50/30">
                        <td class="px-8 py-5">
                            <div class="text-xs font-black text-gray-400 uppercase">{{ $waiting->user_name }}</div>
                            <div class="text-[8px] text-gray-400 font-bold mt-1 uppercase italic">Presente en Planta</div>
                        </td>
                        <td class="px-8 py-5 text-center"><span class="bg-gray-100 px-3 py-1 rounded-full text-[9px] font-black text-gray-400 uppercase italic">Esperando</span></td>
                        <td class="px-8 py-5 text-center text-[10px] font-bold text-gray-400 italic">{{ \Carbon\Carbon::parse($waiting->start_time)->format('h:i A') }}</td>
                        <td class="px-8 py-5 text-right text-[8px] font-black text-gray-300 italic">Sin Viaje Iniciado</td>
                    </tr>
                    @empty
                    @endforelse

                    @if($driversInRoute->isEmpty() && count($onlyAttendance) === 0)
                    <tr>
                        <td colspan="4" class="px-8 py-10 text-center opacity-40 text-[10px] font-black uppercase italic">No hay actividad en vivo actualmente</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- BLOQUE 2: RECORRIDOS FINALIZADOS (DINÁMICO POR FILTRO) --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden mb-12">
        <div class="px-8 py-6 bg-gray-900 flex justify-between items-center">
            <h3 class="font-black text-white text-base uppercase italic tracking-tighter">Historial de Recorridos</h3>
            <span class="text-[10px] font-black text-orange-400 uppercase italic">
                Periodo: {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase">Conductor</th>
                        <th class="px-8 py-4 text-left text-[9px] font-black text-gray-400 uppercase">Ruta</th>
                        <th class="px-8 py-4 text-center text-[9px] font-black text-gray-400 uppercase">Distancia</th>
                        <th class="px-8 py-4 text-right text-[9px] font-black text-gray-400 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($completedToday as $trip)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-5">
                            <div class="text-xs font-black text-gray-800 uppercase leading-none">{{ $trip->user->name }}</div>
                            <div class="text-[8px] text-gray-400 font-bold mt-1 uppercase italic">
                                Finalizó: {{ \Carbon\Carbon::parse($trip->end_time)->format('d/m/Y h:i A') }}
                            </div>
                        </td>
                        <td class="px-8 py-5 text-[10px] font-bold text-gray-600 uppercase">{{ $trip->origin }} <span class="text-reversso">→</span> {{ $trip->destination }}</td>
                        <td class="px-8 py-5 text-center"><span class="text-[10px] font-black text-gray-900 italic bg-gray-100 px-3 py-1 rounded-lg">{{ $trip->end_odometer - $trip->start_odometer }} KM</span></td>
                        <td class="px-8 py-5 text-right"><a href="{{ route('logistics.trip.show', $trip->id) }}" class="text-[9px] font-black text-gray-900 border-b-2 border-reversso uppercase italic">Ver Detalle</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center opacity-40 text-[10px] font-black uppercase italic italic">No se encontraron recorridos finalizados en este rango</td>
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