@extends('layouts.crm')

@section('module_title', 'Logística / Historial de Viajes')

@section('content')
<div class="max-w-6xl mx-auto px-2 md:px-0">

    {{-- CABECERA Y FILTRO: Adaptable --}}
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <a href="{{ route('logistics.index') }}" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-reversso transition-colors flex items-center gap-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                </svg>
                Volver al Dashboard
            </a>
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tighter italic uppercase mt-2">Historial de Viajes</h2>
        </div>

        {{-- SELECTOR DE FECHA --}}
        <form action="{{ route('logistics.history') }}" method="GET" class="w-full md:w-auto bg-white p-2 rounded-2xl md:rounded-3xl shadow-sm border border-gray-100 flex items-center gap-2">
            <input type="date" name="date" value="{{ $dateFilter }}"
                class="flex-1 md:flex-none bg-transparent border-none font-black text-xs md:text-sm text-gray-700 focus:ring-0 cursor-pointer">
            <button type="submit" class="bg-gray-900 text-white px-5 md:px-6 py-2.5 rounded-xl md:rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black active:scale-95 transition-all">
                Filtrar
            </button>
        </form>
    </div>

    {{-- CONTENEDOR DE RESULTADOS --}}
    <div class="bg-white shadow-xl md:shadow-2xl rounded-[35px] md:rounded-[40px] border border-gray-100 overflow-hidden mb-12">
        <div class="px-6 md:px-10 py-6 md:py-8 border-b border-gray-50 bg-gray-50/30">
            <h3 class="font-black text-gray-900 text-[11px] md:text-sm tracking-widest uppercase italic">
                Registros: {{ \Carbon\Carbon::parse($dateFilter)->translatedFormat('d \d\e M, Y') }}
            </h3>
        </div>

        {{-- VISTA DESKTOP: Tabla Tradicional --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-10 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Ruta Detallada</th>
                        <th class="px-10 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Tiempos</th>
                        <th class="px-10 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Kilometraje</th>
                        <th class="px-10 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @forelse($trips as $trip)
                    <tr class="hover:bg-orange-50/20 transition-colors">
                        <td class="px-10 py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-reversso font-black text-xs italic">
                                    {{ $loop->iteration }}
                                </div>
                                <div>
                                    <div class="text-sm font-black text-gray-900 uppercase tracking-tighter">{{ $trip->origin }}</div>
                                    <div class="text-[10px] text-reversso font-bold uppercase tracking-tighter italic">→ {{ $trip->destination ?? 'Sin destino' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-6 text-center">
                            <div class="text-xs font-black text-gray-700">
                                {{ \Carbon\Carbon::parse($trip->start_time)->format('H:i') }} - {{ $trip->end_time ? \Carbon\Carbon::parse($trip->end_time)->format('H:i') : '--:--' }}
                            </div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter italic">Hora Local</div>
                        </td>
                        <td class="px-10 py-6 text-center">
                            <div class="text-sm font-black text-gray-900">
                                {{ $trip->end_odometer ? ($trip->end_odometer - $trip->start_odometer) : '0' }}
                            </div>
                            <div class="text-[9px] text-gray-400 font-black uppercase italic">KM Totales</div>
                        </td>
                        <td class="px-10 py-6 text-right">
                            <p class="text-[11px] text-gray-500 font-medium italic max-w-xs ml-auto leading-relaxed">
                                {{ $trip->observations ?? 'Sin novedades reportadas' }}
                            </p>
                        </td>
                    </tr>
                    @empty
                    {{-- Empty state handling --}}
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- VISTA MOBILE: Cards tipo Timeline --}}
        <div class="md:hidden divide-y divide-gray-50 bg-white">
            @forelse($trips as $trip)
            <div class="p-6 relative">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-gray-900 text-white flex items-center justify-center text-[10px] font-black italic">
                            {{ $loop->iteration }}
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none">Trayecto</p>
                            <h4 class="text-xs font-black text-gray-900 uppercase mt-1">{{ $trip->origin }}</h4>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-900 italic">
                            {{ \Carbon\Carbon::parse($trip->start_time)->format('H:i') }}
                            @if($trip->end_time) - {{ \Carbon\Carbon::parse($trip->end_time)->format('H:i') }} @endif
                        </p>
                    </div>
                </div>

                <div class="ml-10">
                    <div class="text-[10px] text-reversso font-bold uppercase italic mb-3">
                        ↓ {{ $trip->destination ?? 'En curso...' }}
                    </div>

                    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3">
                        <div>
                            <span class="text-[8px] font-black text-gray-400 uppercase block">Distancia</span>
                            <span class="text-xs font-black text-gray-900">{{ $trip->end_odometer ? ($trip->end_odometer - $trip->start_odometer) : '0' }} KM</span>
                        </div>
                        <div class="text-right max-w-[150px]">
                            <span class="text-[8px] font-black text-gray-400 uppercase block">Obs.</span>
                            <span class="text-[9px] font-bold text-gray-500 italic truncate block">{{ $trip->observations ?? 'Sin novedades' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-10 py-20 text-center">
                <p class="text-gray-400 font-black italic text-[10px] uppercase tracking-widest">No hay viajes registrados</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
