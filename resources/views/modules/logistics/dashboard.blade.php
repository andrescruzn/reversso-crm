@extends('layouts.crm')

@section('module_title', 'Operaciones Logísticas')

@section('content')
<div class="max-w-6xl mx-auto px-2 md:px-0">

    {{-- AVISO DE JORNADA CERRADA --}}
    @if(empty($activeAttendance))
    <div class="mb-8 p-6 md:p-8 bg-orange-50 border-2 border-dashed border-orange-200 rounded-[30px] flex flex-col items-center text-center animate-pulse">
        <div class="w-10 h-10 bg-orange-200 rounded-full flex items-center justify-center text-orange-600 mb-4 font-black">!</div>
        <h3 class="text-orange-900 font-black uppercase italic tracking-tighter text-base md:text-lg">Jornada Laboral No Iniciada</h3>
        <p class="text-orange-700 text-[10px] font-bold uppercase tracking-widest mt-1">Debes marcar tu entrada para registrar viajes.</p>
        <a href="{{ route('dashboard') }}" class="mt-4 px-6 py-3 bg-orange-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-orange-700 transition-colors">Volver al Inicio</a>
    </div>
    @endif

    <div class="{{ (empty($activeAttendance)) ? 'opacity-30 pointer-events-none grayscale' : '' }} transition-all duration-500">

        {{-- HEADER DEL PANEL --}}
        <div class="mb-6 md:mb-8 bg-reversso rounded-[35px] md:rounded-[40px] p-6 md:p-10 shadow-2xl text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 bg-white/20 rounded-full text-[8px] md:text-[9px] font-black uppercase tracking-widest">Módulo 01</span>
                    <h2 class="text-2xl md:text-3xl font-black tracking-tight italic uppercase">Panel de Viajes</h2>
                </div>
                <p class="text-orange-100 text-xs md:text-sm mb-6 md:mb-8 font-medium italic">Gestión de rutas y kilometraje.</p>

                <div class="flex flex-col sm:flex-row gap-4">
                    @if(empty($activeTracking))
                    <button onclick="toggleModal('modal-start')" class="w-full bg-white text-reversso font-black py-4 md:py-5 rounded-2xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest text-[11px] md:text-xs italic">
                        🚢 Iniciar Nuevo Viaje
                    </button>
                    @else
                    <button onclick="toggleModal('modal-end')" class="w-full bg-gray-900 border-2 border-gray-800 text-white font-black py-4 md:py-5 rounded-2xl shadow-lg hover:bg-black active:scale-95 transition-all uppercase tracking-widest text-[11px] md:text-xs italic">
                        🏁 Finalizar Viaje Actual
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- MÉTRICAS RÁPIDAS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-[30px] p-6 md:p-8 shadow-sm border border-gray-100">
                <p class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">KM Recorridos en el periodo</p>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl md:text-4xl font-black text-reversso">{{ number_format($metrics['total_km'] ?? 0, 1) }}</span>
                    <span class="text-[10px] font-bold text-gray-400 italic">KM TOTALES</span>
                </div>
            </div>
        </div>

        {{-- HISTORIAL DE VIAJES --}}
        <div class="bg-white shadow-sm rounded-[35px] md:rounded-[40px] border border-gray-100 overflow-hidden mb-12">
            <div class="px-6 md:px-10 py-6 md:py-7 border-b border-gray-50 bg-gray-50/30 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-center md:text-left">
                    <h3 class="font-black text-gray-900 text-base md:text-lg tracking-tighter uppercase italic">Historial de Viajes</h3>
                    <p class="text-[9px] md:text-[10px] font-black text-reversso uppercase italic tracking-widest">
                        Rango: {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}
                    </p>
                </div>

                {{-- FILTRO --}}
                <form action="{{ route('logistics.index') }}" method="GET" class="flex flex-wrap items-center gap-2 justify-center">
                    <input type="date" name="from" value="{{ $fromDate }}" class="bg-gray-100 border-none rounded-xl px-3 py-2 text-[10px] font-black">
                    <input type="date" name="to" value="{{ $toDate }}" class="bg-gray-100 border-none rounded-xl px-3 py-2 text-[10px] font-black">
                    <input type="text" name="search" value="{{ $search }}" placeholder="BUSCAR..." class="bg-gray-100 border-none rounded-xl px-4 py-2 text-[10px] font-black w-32 md:w-auto">
                    <button type="submit" class="bg-gray-900 text-white p-2.5 rounded-xl hover:bg-black transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    @if(request('from')) <a href="{{ route('logistics.index') }}" class="text-[9px] font-black text-gray-400 uppercase">Limpiar</a> @endif
                </form>
            </div>

            {{-- TABLA --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-10 py-4 text-left text-[10px] font-black text-gray-400 uppercase">Fecha</th>
                            <th class="px-10 py-4 text-left text-[10px] font-black text-gray-400 uppercase">Trayecto</th>
                            <th class="px-10 py-4 text-center text-[10px] font-black text-gray-400 uppercase">Inicio</th>
                            <th class="px-10 py-4 text-center text-[10px] font-black text-gray-400 uppercase">Distancia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($todayTrips as $trip)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-10 py-5 text-[10px] font-black text-gray-400">{{ \Carbon\Carbon::parse($trip->date)->format('d/m/Y') }}</td>
                            <td class="px-10 py-5">
                                <div class="text-sm font-black text-gray-900 uppercase tracking-tighter">{{ $trip->origin }}</div>
                                <div class="text-[10px] text-reversso font-bold italic">→ {{ $trip->destination ?? 'En curso...' }}</div>
                            </td>
                            <td class="px-10 py-5 text-center text-xs font-black text-gray-600">{{ \Carbon\Carbon::parse($trip->start_time)->format('h:i A') }}</td>
                            <td class="px-10 py-5 text-center">
                                @if($trip->end_odometer)
                                <span class="text-sm font-black text-gray-900">{{ number_format($trip->end_odometer - $trip->start_odometer, 1) }}</span> <span class="text-[9px] font-bold text-gray-400 uppercase">Km</span>
                                @else
                                <span class="text-[10px] font-black text-orange-500 animate-pulse uppercase italic">En curso</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-10 py-20 text-center text-xs font-bold text-gray-400 uppercase italic">Sin registros</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($todayTrips->hasPages())
            <div class="px-6 md:px-10 py-6 border-t border-gray-50 bg-gray-50/20">
                {{ $todayTrips->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- MODALS --}}
<div id="modal-start" class="hidden fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="toggleModal('modal-start')"></div>
    <div class="relative bg-white w-full max-w-md rounded-t-[30px] sm:rounded-[30px] p-8 md:p-10 shadow-2xl">
        <form action="{{ route('logistics.start') }}" method="POST">
            @csrf
            <h3 class="text-xl md:text-2xl font-black text-gray-900 mb-6 uppercase italic tracking-tighter text-center">Iniciar Viaje</h3>
            <div class="space-y-4">
                <input type="text" name="origin" required placeholder="Origen" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl font-bold">
                <input type="number" name="start_odometer" required placeholder="Kilometraje Inicial" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl font-bold">
            </div>
            <button type="submit" class="w-full mt-8 bg-gray-900 text-white font-black py-4 rounded-xl uppercase text-xs tracking-widest shadow-xl">Confirmar Salida</button>
        </form>
    </div>
</div>

@if($activeTracking)
<div id="modal-end" class="hidden fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="toggleModal('modal-end')"></div>
    <div class="relative bg-white w-full max-w-md rounded-t-[30px] sm:rounded-[30px] p-8 md:p-10 shadow-2xl">
        <form action="{{ route('logistics.end') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $activeTracking->id }}">
            <h3 class="text-xl md:text-2xl font-black text-gray-900 mb-6 uppercase italic tracking-tighter text-center">Finalizar Viaje</h3>
            <div class="space-y-4">
                <input type="text" name="destination" required placeholder="Destino" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl font-bold">
                <input type="number" name="end_odometer" required placeholder="Kilometraje Final" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl font-bold">
            </div>
            <button type="submit" class="w-full mt-8 bg-reversso text-white font-black py-4 rounded-xl uppercase text-xs tracking-widest shadow-xl">Cerrar Registro</button>
        </form>
    </div>
</div>
@endif

<script>
    function toggleModal(id) {
        document.getElementById(id)?.classList.toggle('hidden');
    }
</script>
@endsection