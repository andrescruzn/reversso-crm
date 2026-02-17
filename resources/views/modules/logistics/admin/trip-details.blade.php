@extends('layouts.crm')

@section('module_title', 'Detalles de Conducción')

@section('content')
<div class="max-w-5xl mx-auto px-2 md:px-0">
    {{-- ENCABEZADO --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <a href="{{ route('logistics.index') }}" class="text-[10px] font-black text-reversso uppercase tracking-widest hover:underline flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al Panel
            </a>
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 italic uppercase tracking-tighter mt-2">Expediente de Viaje</h2>
        </div>

        {{-- BADGE DINÁMICO DE ESTADO --}}
        <div class="inline-flex self-start md:self-center px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest
            @if($trip->approved_by === 0)
                bg-red-100 text-red-700
            @elseif($trip->approved_at)
                bg-green-100 text-green-700
            @else
                bg-orange-100 text-orange-700
            @endif">

            @if($trip->approved_by === 0)
            Estado: Desaprobado
            @elseif($trip->approved_at)
            Estado: Auditado / Aprobado
            @else
            Estado: Pendiente de Auditoría
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
        {{-- COLUMNA IZQUIERDA: Info Conductor y Tiempos --}}
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <p class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Conductor Responsable</p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-2xl flex-shrink-0 flex items-center justify-center text-reversso font-black italic text-lg">
                        {{ substr($trip->user->name, 0, 1) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-black text-gray-900 uppercase truncate">{{ $trip->user->name }}</p>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">ID: {{ str_pad((string)$trip->user->id, 5, '0', STR_PAD_LEFT) }}</p>
                    </div>
                </div>
            </div>

            @if($trip->vehicle_plate)
            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <p class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Vehículo</p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-100 rounded-2xl flex-shrink-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17h.01M16 17h.01M5.2 14h13.6c1.12 0 1.68 0 2.108-.218a2 2 0 00.874-.874C22 12.48 22 11.92 22 10.8v-.6c0-1.12 0-1.68-.218-2.108a2 2 0 00-.874-.874C20.48 7 19.92 7 18.8 7H5.2c-1.12 0-1.68 0-2.108.218a2 2 0 00-.874.874C2 8.52 2 9.08 2 10.2v.6c0 1.12 0 1.68.218 2.108a2 2 0 00.874.874C3.52 14 4.08 14 5.2 14zM6.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm11 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM4 7l1.5-4h13L20 7M5 14v3h14v-3"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-black text-gray-900 uppercase tracking-wider">{{ $trip->vehicle_plate }}</p>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Placa</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-white p-6 rounded-[30px] shadow-sm border border-gray-100">
                <p class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Registro de Tiempos</p>
                <div class="space-y-4">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-gray-400 uppercase italic">Inicio de Viaje</span>
                        <span class="text-sm font-bold text-gray-700 leading-tight">{{ \Carbon\Carbon::parse($trip->start_time)->format('d/m/Y - H:i A') }}</span>
                        @if($trip->origin)
                        <span class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-reversso" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $trip->origin }}
                        </span>
                        @endif
                    </div>
                    @if($trip->end_time)
                    <div class="flex flex-col">
                        <span class="text-[9px] font-black text-gray-400 uppercase italic">Fin de Viaje</span>
                        <span class="text-sm font-bold text-gray-700 leading-tight">{{ \Carbon\Carbon::parse($trip->end_time)->format('d/m/Y - H:i A') }}</span>
                        @if($trip->destination)
                        <span class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $trip->destination }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: Métricas y Notas --}}
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white p-6 md:p-8 rounded-[35px] md:rounded-[40px] shadow-sm border border-gray-100">
                <h3 class="font-black text-gray-900 text-lg uppercase italic tracking-tight mb-6">Métricas de Recorrido</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-8">
                    <div class="p-5 md:p-6 bg-gray-50 rounded-[24px] border border-gray-100">
                        <span class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Odómetro Inicial</span>
                        <div class="flex items-baseline">
                            <span class="text-2xl md:text-3xl font-black text-gray-900 tracking-tighter">{{ number_format($trip->start_odometer ?? 0, 0) }}</span>
                            <span class="text-[10px] font-bold text-gray-400 ml-1">KM</span>
                        </div>
                    </div>
                    <div class="p-5 md:p-6 bg-gray-50 rounded-[24px] border border-gray-100">
                        <span class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Odómetro Final</span>
                        <div class="flex items-baseline">
                            <span class="text-2xl md:text-3xl font-black text-gray-900 tracking-tighter">{{ $trip->end_odometer ? number_format($trip->end_odometer, 0) : '---' }}</span>
                            <span class="text-[10px] font-bold text-gray-400 ml-1">KM</span>
                        </div>
                    </div>
                </div>

                @if($trip->end_odometer)
                <div class="mt-8 pt-8 border-t border-dashed border-gray-200">
                    <div class="text-center md:text-left">
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Distancia Total</p>
                        <p class="text-xl md:text-2xl font-black text-reversso italic tracking-tighter">{{ $trip->end_odometer - $trip->start_odometer }} KM Recorridos</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="bg-white p-6 md:p-8 rounded-[35px] md:rounded-[40px] shadow-sm border border-gray-100">
                <h3 class="font-black text-gray-900 text-lg uppercase italic tracking-tight mb-4">Notas y Observaciones</h3>
                <div class="p-6 bg-orange-50/50 rounded-[24px] text-sm text-gray-600 italic leading-relaxed">
                    {{ $trip->observations ?? 'El conductor no registró observaciones adicionales para este trayecto.' }}
                </div>
            </div>

            {{-- BOTONES DE ACCIÓN ADMINISTRATIVA --}}
            <div class="flex flex-col md:flex-row justify-end gap-4 pt-4">
                @if($trip->approved_by === 0)
                <form action="{{ route('logistics.approve', $trip->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full md:w-auto bg-gray-900 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest active:scale-95 shadow-lg">
                        Re-Aprobar Trayecto
                    </button>
                </form>
                @elseif(!$trip->approved_at)
                <form action="{{ route('logistics.disapprove', $trip->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full md:w-auto border border-red-200 text-red-600 px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-50 transition-all">
                        Desaprobar
                    </button>
                </form>
                <form action="{{ route('logistics.approve', $trip->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full md:w-auto bg-green-600 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest active:scale-95 shadow-lg">
                        Aprobar Registro
                    </button>
                </form>
                @else
                {{-- SI YA ESTÁ APROBADO, PERMITIR DESAPROBAR POR SI HUBO ERROR --}}
                <form action="{{ route('logistics.disapprove', $trip->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full md:w-auto border border-gray-200 text-gray-400 px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:text-red-600 hover:border-red-200 transition-all">
                        Anular Aprobación
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
