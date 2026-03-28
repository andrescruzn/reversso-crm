@extends('layouts.crm')

@section('module_title', 'MI BITÁCORA DE VIAJES')

@section('content')

{{-- 1. HEADER --}}
<div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-6 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center gap-3">
        @if(!$activeTracking)

            {{-- ✅ Si NO hay jornada activa, NO permitir iniciar viaje --}}
            @if(!$activeAttendance)
                <div class="bg-white border border-orange-100 p-4 rounded-2xl shadow-sm w-max">
                    <p class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Jornada finalizada</p>
                    <p class="text-xs font-black text-gray-900 italic">Debes marcar entrada para iniciar un viaje.</p>
                </div>
            @else
                <button
                    type="button"
                    onclick="toggleGlobalModal('modal-start-trip')"
                    class="w-full lg:w-auto bg-reversso text-white px-8 py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-orange-500/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-3"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    Iniciar Nuevo Viaje
                </button>
            @endif

        @else
            <div class="bg-white border-2 border-green-100 p-4 rounded-2xl flex items-center gap-4 shadow-sm w-max">
                <div class="relative flex">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75 animate-ping"></span>
                    <div class="relative bg-green-500 text-white p-2 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-[9px] font-black text-green-500 uppercase tracking-widest">En ruta activa</p>
                    <p class="text-xs font-black text-gray-900 italic">
                        <span class="bg-gray-900 text-white px-2 py-0.5 rounded mr-1">{{ $activeTracking->vehicle_plate ?? 'S/P' }}</span>
                        • {{ $activeTracking->origin }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Siempre visible: registrar viaje pasado --}}
        <button
            type="button"
            onclick="toggleGlobalModal('modal-manual-trip')"
            class="w-full lg:w-auto bg-white border border-gray-200 text-gray-700 px-6 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:border-reversso hover:text-reversso transition-all flex items-center justify-center gap-3"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Registrar Viaje Pasado
        </button>
    </div>

    {{-- FILTROS --}}
    <form action="{{ route('logistics.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-2 bg-white p-2 rounded-[25px] shadow-sm border border-gray-100">
        <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-xl border border-gray-100">
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Del</span>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="bg-transparent border-none p-0 text-xs font-bold focus:ring-0">
        </div>

        <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-xl border border-gray-100">
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Al</span>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="bg-transparent border-none p-0 text-xs font-bold focus:ring-0">
        </div>

        {{-- ✅ LUPA Y RESET (perfecto centrado y mismo tamaño) --}}
        <div class="flex gap-2">
            <button
                type="submit"
                class="w-11 h-11 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all inline-flex items-center justify-center leading-none"
                aria-label="Buscar"
                title="Buscar"
            >
                <svg class="w-5 h-5 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>

            <a
                href="{{ route('logistics.index') }}"
                class="w-11 h-11 bg-gray-100 text-gray-400 rounded-2xl hover:text-red-500 transition-all border border-gray-100 inline-flex items-center justify-center leading-none"
                aria-label="Reset"
                title="Reset"
            >
                <svg class="w-5 h-5 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </a>
        </div>
    </form>
</div>

{{-- ✅ RESUMEN / MÉTRICAS --}}
@if(isset($metrics))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Viajes</p>
            <p class="text-2xl font-black text-gray-900 mt-1">{{ $metrics['total_trips'] ?? 0 }}</p>
            <p class="text-[10px] text-gray-400 font-bold mt-1">En el rango seleccionado</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Kilómetros</p>
            <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format((float)($metrics['total_km'] ?? 0)) }}</p>
            <p class="text-[10px] text-gray-400 font-bold mt-1">Recorridos totales</p>
        </div>

        <div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Horas</p>
            <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format((float)($metrics['total_hours'] ?? 0), 0) }}</p>
            <p class="text-[10px] text-gray-400 font-bold mt-1">Acumuladas (cerrados)</p>
        </div>
    </div>
@endif

{{-- 2. GRILLA --}}
<div class="bg-white rounded-[35px] shadow-2xl border border-gray-100 overflow-hidden text-xs">

    {{-- VISTA DESKTOP: Tabla --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    <th class="p-6">Vehículo / Ref</th>
                    <th class="p-6">Ruta (Origen → Destino)</th>
                    <th class="p-6 text-center">Kilometraje</th>
                    <th class="p-6 text-center">Seguimiento</th>
                    <th class="p-6 text-right">Acción</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-50">
                @forelse($trips as $trip)
                    @php
                        $deltaKm = null;
                        if ($trip->end_odometer !== null && $trip->start_odometer !== null) {
                            $deltaKm = max(0, (int)$trip->end_odometer - (int)$trip->start_odometer);
                        }
                    @endphp

                    <tr class="hover:bg-gray-50/30 transition-all group">
                        <td class="p-6">
                            <div class="flex flex-col gap-1">
                                <span class="bg-gray-900 text-white text-[9px] px-2 py-0.5 rounded-md font-black w-max shadow-sm">{{ $trip->vehicle_plate ?? 'S/P' }}</span>
                                <span class="text-[9px] font-bold text-gray-300 uppercase">ID: #{{ $trip->id }}</span>
                            </div>
                        </td>

                        <td class="p-6">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-black uppercase text-gray-900 tracking-tighter">{{ $trip->origin }}</span>
                                @if($trip->destination)
                                    <span class="text-reversso font-bold">→</span>
                                    <span class="font-black uppercase text-gray-900 tracking-tighter">{{ $trip->destination }}</span>
                                @endif
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold">
                                {{ \Carbon\Carbon::parse($trip->start_time)->format('d M, h:i A') }}
                            </p>
                        </td>

                        <td class="p-6 font-mono text-center">
                            <div class="flex flex-col items-center gap-1">
                                <div>
                                    <span class="text-gray-400">{{ number_format((int)$trip->start_odometer) }}</span>
                                    @if($trip->end_odometer !== null)
                                        <span class="text-reversso mx-1 font-bold">→</span>
                                        <span class="font-black text-gray-900">{{ number_format((int)$trip->end_odometer) }}</span>
                                    @endif
                                </div>

                                @if($deltaKm !== null)
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        +{{ number_format($deltaKm) }} km
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="p-6">
                            <div class="flex items-center justify-center gap-4">
                                <span class="px-3 py-1 rounded-full font-black uppercase text-[8px] border {{ !$trip->end_time ? 'bg-blue-50 text-blue-600 border-blue-100 animate-pulse' : 'bg-gray-50 text-gray-400 border-gray-100' }}">
                                    {{ !$trip->end_time ? 'En Ruta' : 'Cerrado' }}
                                </span>
                            </div>
                        </td>

                        <td class="p-6 text-right">
                            <div class="inline-flex items-center gap-2">
                                @if(!$trip->end_time)
                                   <button
                                        type="button"
                                        onclick="openEndTripModal(this)"
                                        data-id="{{ $trip->id }}"
                                        data-start="{{ (int) $trip->start_odometer }}"
                                        class="bg-red-500 text-white px-5 py-2.5 rounded-2xl font-black uppercase text-[9px] hover:bg-red-600 transition-all"
                                    >
                                        Finalizar
                                    </button>
                                @endif

                                @if(!empty($trip->observations))
                                    <button
                                        type="button"
                                        onclick="openObservationsModal(@js($trip->observations))"
                                        class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all"
                                        title="Ver observaciones"
                                    >
                                        <svg class="w-5 h-5 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                @endif

                                @if($trip->end_time)
                                    <form action="{{ route('logistics.manual-trip.delete', $trip->id) }}" method="POST"
                                          data-confirm="¿Eliminar este viaje? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all"
                                            title="Eliminar viaje"
                                        >
                                            <svg class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center text-gray-300 font-black uppercase text-xs tracking-widest italic">Sin registros</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- VISTA MOBILE: Cards --}}
    <div class="md:hidden divide-y divide-gray-50">
        @forelse($trips as $trip)
            @php
                $deltaKm = null;
                if ($trip->end_odometer !== null && $trip->start_odometer !== null) {
                    $deltaKm = max(0, (int)$trip->end_odometer - (int)$trip->start_odometer);
                }
            @endphp
            <div class="p-5">
                {{-- Header: placa + estado --}}
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <span class="bg-gray-900 text-white text-[9px] px-2.5 py-1 rounded-lg font-black shadow-sm">{{ $trip->vehicle_plate ?? 'S/P' }}</span>
                        <span class="text-[9px] font-bold text-gray-300 uppercase">ID: #{{ $trip->id }}</span>
                    </div>
                    <span class="px-3 py-1 rounded-full font-black uppercase text-[8px] border {{ !$trip->end_time ? 'bg-blue-50 text-blue-600 border-blue-100 animate-pulse' : 'bg-gray-50 text-gray-400 border-gray-100' }}">
                        {{ !$trip->end_time ? 'En Ruta' : 'Cerrado' }}
                    </span>
                </div>

                {{-- Ruta --}}
                <div class="mb-3">
                    <p class="text-xs font-black uppercase text-gray-900 tracking-tighter">
                        {{ $trip->origin }}
                        @if($trip->destination)
                            <span class="text-reversso mx-1">→</span> {{ $trip->destination }}
                        @endif
                    </p>
                    <p class="text-[10px] text-gray-400 font-bold mt-0.5">
                        {{ \Carbon\Carbon::parse($trip->start_time)->format('d M, h:i A') }}
                    </p>
                </div>

                {{-- Kilometraje --}}
                <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3 mb-3">
                    <div>
                        <span class="text-[8px] font-black text-gray-400 uppercase block">Km Inicio</span>
                        <span class="text-xs font-black text-gray-700">{{ number_format((int)$trip->start_odometer) }}</span>
                    </div>
                    @if($trip->end_odometer !== null)
                        <div class="text-center">
                            <span class="text-[8px] font-black text-gray-400 uppercase block">Km Final</span>
                            <span class="text-xs font-black text-gray-900">{{ number_format((int)$trip->end_odometer) }}</span>
                        </div>
                    @endif
                    @if($deltaKm !== null)
                        <div class="text-right">
                            <span class="text-[8px] font-black text-gray-400 uppercase block">Distancia</span>
                            <span class="text-xs font-black text-reversso">+{{ number_format($deltaKm) }} km</span>
                        </div>
                    @endif
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-2">
                    @if(!$trip->end_time)
                        <button
                            type="button"
                            onclick="openEndTripModal(this)"
                            data-id="{{ $trip->id }}"
                            data-start="{{ (int) $trip->start_odometer }}"
                            class="flex-1 bg-red-500 text-white px-4 py-2.5 rounded-2xl font-black uppercase text-[9px] text-center hover:bg-red-600 transition-all"
                        >
                            Finalizar Viaje
                        </button>
                    @endif

                    @if(!empty($trip->observations))
                        <button
                            type="button"
                            onclick="openObservationsModal(@js($trip->observations))"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all"
                            title="Ver observaciones"
                        >
                            <svg class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    @endif

                    @if($trip->end_time)
                        <form action="{{ route('logistics.manual-trip.delete', $trip->id) }}" method="POST"
                              data-confirm="¿Eliminar este viaje? Esta acción no se puede deshacer.">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all"
                                title="Eliminar viaje"
                            >
                                <svg class="w-4 h-4 block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-300 font-black uppercase text-xs tracking-widest italic">Sin registros</div>
        @endforelse
    </div>

    {{-- PAGINACION --}}
    <div class="px-6 py-5 border-t border-gray-100">
        {{ $trips->links() }}
    </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL NUEVO REGISTRO --}}
{{-- ========================================================= --}}
<div id="modal-start-trip" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="toggleGlobalModal('modal-start-trip')"></div>

    <div class="relative bg-white w-full max-w-md rounded-[40px] p-10 shadow-2xl">
        <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase italic text-center tracking-tighter">Nuevo Registro</h3>

        {{-- ✅ ERRORES SOLO DEL MODAL START --}}
        @if($errors->hasBag('startTrip') && $errors->startTrip->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 text-[10px] font-black uppercase rounded-xl">
                @foreach($errors->startTrip->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('logistics.start') }}" method="POST" class="space-y-6" autocomplete="off">
            @csrf
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Vehículo (Placa)</label>
                <div class="relative combobox-wrapper">
                    <input
                        type="text"
                        name="vehicle_plate"
                        id="vehicle_plate"
                        value="{{ old('vehicle_plate') }}"
                        required
                        autocomplete="off"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none uppercase"
                        placeholder="ABC-123"
                    >
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Punto de Origen</label>
                <div class="relative combobox-wrapper">
                    <input
                        type="text"
                        name="origin"
                        id="origin"
                        value="{{ old('origin') }}"
                        required
                        autocomplete="off"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                        placeholder="¿Dónde inicias?"
                    >
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Km Inicial</label>
                <input
                    type="number"
                    name="start_odometer"
                    value="{{ old('start_odometer') }}"
                    required
                    min="0"
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                    placeholder="00000"
                >
            </div>

            <button type="submit" class="w-full bg-reversso text-white font-black py-5 rounded-2xl uppercase text-xs tracking-widest shadow-xl transition-all">
                Iniciar Viaje
            </button>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL FINALIZAR VIAJE --}}
{{-- ========================================================= --}}
<div id="modal-end-trip" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeEndTripModal()"></div>

    <div class="relative bg-white w-full max-w-md rounded-[40px] p-10 shadow-2xl">
        <h3 class="text-2xl font-black text-gray-900 mb-4 uppercase italic text-center tracking-tighter">Finalizar Viaje</h3>

        {{-- ✅ ERRORES SOLO DEL MODAL END --}}
        @if($errors->hasBag('endTrip') && $errors->endTrip->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 text-[10px] font-black uppercase rounded-xl">
                @foreach($errors->endTrip->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('logistics.end') }}" method="POST" class="space-y-6" autocomplete="off">
            @csrf
            <input type="hidden" name="tracking_id" id="end_tracking_id" value="{{ old('tracking_id') }}">
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Km Inicial</label>
                <input
                    type="number"
                    id="start_odometer_display"
                    value=""
                    readonly
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold text-gray-500 focus:ring-0 outline-none cursor-not-allowed"
                    placeholder="--"
                >
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Destino Final</label>
                <div class="relative combobox-wrapper">
                    <input type="text" name="destination" id="destination" value="{{ old('destination') }}" required autocomplete="off" class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none" placeholder="¿A dónde llegaste?">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Km Final</label>
                <input type="number" name="end_odometer" value="{{ old('end_odometer') }}" required min="0" class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none" placeholder="00000">
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Observaciones</label>
                <textarea
                    name="observations"
                    rows="3"
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none resize-none"
                    placeholder="Opcional: novedades del viaje..."
                >{{ old('observations') }}</textarea>
            </div>

            <button type="submit" class="w-full bg-gray-900 text-white font-black py-5 rounded-2xl uppercase text-xs tracking-widest shadow-xl transition-all">
                Cerrar Registro
            </button>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL VIAJE MANUAL (RETROACTIVO) --}}
{{-- ========================================================= --}}
<div id="modal-manual-trip" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="toggleGlobalModal('modal-manual-trip')"></div>

    <div class="relative bg-white w-full max-w-lg rounded-[40px] p-10 shadow-2xl max-h-[90vh] overflow-y-auto">
        <button onclick="toggleGlobalModal('modal-manual-trip')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase italic text-center tracking-tighter">Viaje Pasado</h3>

        @if($errors->hasBag('manualTrip') && $errors->manualTrip->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 text-[10px] font-black uppercase rounded-xl">
                @foreach($errors->manualTrip->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('logistics.manual-trip') }}" method="POST" class="space-y-5" autocomplete="off">
            @csrf

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Vehículo (Placa)</label>
                <div class="relative combobox-wrapper">
                    <input
                        type="text"
                        name="vehicle_plate"
                        id="mt_vehicle_plate"
                        value="{{ old('vehicle_plate') }}"
                        required
                        autocomplete="off"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none uppercase"
                        placeholder="ABC-123"
                    >
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Fecha y hora de inicio</label>
                <input
                    type="datetime-local"
                    name="start_time"
                    value="{{ old('start_time') }}"
                    required
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                >
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Fecha y hora de fin</label>
                <input
                    type="datetime-local"
                    name="end_time"
                    value="{{ old('end_time') }}"
                    required
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                >
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Origen</label>
                <div class="relative combobox-wrapper">
                    <input
                        type="text"
                        name="origin"
                        id="mt_origin"
                        value="{{ old('origin') }}"
                        required
                        autocomplete="off"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                        placeholder="¿Dónde iniciaste?"
                    >
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Destino</label>
                <div class="relative combobox-wrapper">
                    <input
                        type="text"
                        name="destination"
                        id="mt_destination"
                        value="{{ old('destination') }}"
                        required
                        autocomplete="off"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                        placeholder="¿A dónde llegaste?"
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Km Inicial</label>
                    <input
                        type="number"
                        name="start_odometer"
                        value="{{ old('start_odometer') }}"
                        required
                        min="0"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                        placeholder="00000"
                    >
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Km Final</label>
                    <input
                        type="number"
                        name="end_odometer"
                        value="{{ old('end_odometer') }}"
                        required
                        min="0"
                        class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none"
                        placeholder="00000"
                    >
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 hover:border-orange-200 transition-colors cursor-pointer group"
                 onclick="document.getElementById('mt_is_holiday').click()">
                <div class="flex items-center justify-between">
                    <label for="mt_is_holiday" class="text-xs font-black text-gray-700 uppercase italic cursor-pointer group-hover:text-reversso transition-colors">
                        ¿Es día Festivo?
                    </label>
                    <div class="relative flex items-center">
                        <input type="checkbox" name="is_holiday" value="1" id="mt_is_holiday"
                               {{ old('is_holiday') ? 'checked' : '' }}
                               class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 transition-all checked:border-orange-500 checked:bg-orange-500">
                        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Observaciones</label>
                <textarea
                    name="observations"
                    rows="3"
                    class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none resize-none"
                    placeholder="Opcional: novedades del viaje..."
                >{{ old('observations') }}</textarea>
            </div>

            <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-black py-5 rounded-2xl uppercase text-xs tracking-widest shadow-xl transition-all">
                Registrar Viaje
            </button>
        </form>
    </div>
</div>

{{-- MODAL VER OBSERVACIONES --}}
<div id="modal-view-obs" class="hidden fixed inset-0 z-[110] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeObservationsModal()"></div>

    <div class="relative bg-white w-full max-w-md rounded-[40px] p-10 shadow-2xl">
        <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase italic text-center tracking-tighter">
            Observaciones
        </h3>

        <div class="bg-gray-50 rounded-2xl p-5 text-sm font-bold text-gray-700 whitespace-pre-wrap" id="obs_content"></div>

        <button type="button" class="mt-8 w-full bg-gray-900 text-white font-black py-5 rounded-2xl uppercase text-xs tracking-widest shadow-xl transition-all" onclick="closeObservationsModal()">
            Cerrar
        </button>
    </div>
</div>

<script>
    /**
     * ✅ AUTO-OPEN modal dependiendo de lo que mande el Controller.
     * - start -> session('open_modal') = modal-start-trip
     * - end   -> session('open_modal') = modal-end-trip
     */
    window.addEventListener('load', function () {
        const openModal = @json(session('open_modal'));

        if (!openModal) return;

        if (openModal === 'modal-start-trip') {
            const el = document.getElementById('modal-start-trip');
            if (el) el.classList.remove('hidden');
            return;
        }

        if (openModal === 'modal-end-trip') {
            // Si viene tracking_id, lo seteamos
            const trackingId = @json(old('tracking_id'));
            if (trackingId) {
                const input = document.getElementById('end_tracking_id');
                if (input) input.value = trackingId;
            }
            const el = document.getElementById('modal-end-trip');
            if (el) el.classList.remove('hidden');
            return;
        }

        if (openModal === 'modal-manual-trip') {
            const el = document.getElementById('modal-manual-trip');
            if (el) el.classList.remove('hidden');
            return;
        }
    });

    function openEndTripModal(btnOrId) {
        // Soporta botón o id
        let id = null;
        let start = null;

        // Si llega un elemento del DOM (button)
        if (btnOrId instanceof HTMLElement) {
            id = btnOrId.getAttribute('data-id');
            start = btnOrId.getAttribute('data-start');
        } else {
            // fallback: si solo mandan el id
            id = btnOrId;
        }

        // Set tracking id
        const idInput = document.getElementById('end_tracking_id');
        if (idInput) idInput.value = id || '';

        // Set km inicial readonly
        const startDisplay = document.getElementById('start_odometer_display');
        if (startDisplay) startDisplay.value = start ?? '';

        // end_odometer: min dinámico (start + 1) o limpiar min si no hay start
        const endInput = document.querySelector('#modal-end-trip input[name="end_odometer"]');
        if (endInput) {
            if (start !== null && start !== '' && !Number.isNaN(Number(start))) {
                const minVal = Number(start) + 1;
                endInput.setAttribute('min', String(minVal));
            } else {
                // evita que quede min viejo de un viaje anterior
                endInput.setAttribute('min', '0');
            }
        }

        document.getElementById('modal-end-trip')?.classList.remove('hidden');
    }

    function closeEndTripModal() {
        document.getElementById('modal-end-trip')?.classList.add('hidden');
    }

    function toggleGlobalModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden');
    }

    function openObservationsModal(text) {
        const el = document.getElementById('obs_content');
        if (el) el.textContent = text || '';
        document.getElementById('modal-view-obs').classList.remove('hidden');
    }

    function closeObservationsModal() {
        document.getElementById('modal-view-obs').classList.add('hidden');
    }

    /**
     * Combobox autocompletable reutilizable.
     * Crea un dropdown de sugerencias debajo del input.
     */
    function initCombobox(inputId, suggestions) {
        const input = document.getElementById(inputId);
        if (!input || !suggestions.length) return;

        const wrapper = input.closest('.combobox-wrapper');
        if (!wrapper) return;

        // Crear dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-2xl shadow-lg max-h-48 overflow-y-auto z-50 hidden';
        wrapper.appendChild(dropdown);

        let activeIndex = -1;
        let filtered = [];

        function render() {
            dropdown.innerHTML = '';
            activeIndex = -1;

            const query = input.value.trim().toLowerCase();
            if (!query) {
                dropdown.classList.add('hidden');
                return;
            }

            filtered = suggestions.filter(s => s.toLowerCase().includes(query));
            if (!filtered.length) {
                dropdown.classList.add('hidden');
                return;
            }

            filtered.forEach((item, i) => {
                const opt = document.createElement('div');
                opt.textContent = item;
                opt.className = 'px-4 py-2.5 text-sm font-bold text-gray-700 cursor-pointer hover:bg-orange-50 hover:text-reversso transition-colors';
                if (i === 0) opt.classList.add('rounded-t-2xl');
                if (i === filtered.length - 1) opt.classList.add('rounded-b-2xl');
                opt.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    input.value = item;
                    dropdown.classList.add('hidden');
                    input.focus();
                });
                dropdown.appendChild(opt);
            });

            dropdown.classList.remove('hidden');
        }

        function highlight(idx) {
            const items = dropdown.children;
            for (let i = 0; i < items.length; i++) {
                items[i].classList.toggle('bg-orange-50', i === idx);
                items[i].classList.toggle('text-reversso', i === idx);
            }
        }

        input.addEventListener('input', render);
        input.addEventListener('focus', render);

        input.addEventListener('keydown', function (e) {
            if (dropdown.classList.contains('hidden')) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
                highlight(activeIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                highlight(activeIndex);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                input.value = filtered[activeIndex];
                dropdown.classList.add('hidden');
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
            }
        });

        input.addEventListener('blur', function () {
            // Small delay to allow mousedown on option
            setTimeout(() => dropdown.classList.add('hidden'), 150);
        });
    }

    // Inicializar comboboxes (modal inicio viaje)
    initCombobox('vehicle_plate', @json($plates));
    initCombobox('origin', @json($locations));
    initCombobox('destination', @json($locations));

    // Inicializar comboboxes (modal viaje manual)
    initCombobox('mt_vehicle_plate', @json($plates));
    initCombobox('mt_origin', @json($locations));
    initCombobox('mt_destination', @json($locations));
</script>

@endsection
