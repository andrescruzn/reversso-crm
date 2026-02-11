{{-- resources/views/home.blade.php --}}
@extends('layouts.crm')

@section('module_title', 'Escritorio Inicial')

@section('content')
<div class="max-w-4xl mx-auto px-2 md:px-0">
    {{-- HEADER: Texto ajustable para evitar desbordes --}}
    <header class="mb-8 md:mb-12">
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter mb-2 uppercase italic leading-none">
            Bienvenido, <span class="text-reversso">{{ explode(' ', auth()->user()->name)[0] }}</span>
        </h1>
        <p class="text-gray-500 font-bold italic uppercase text-[10px] md:text-xs tracking-widest">
            Seleccione un módulo para operar.
        </p>
    </header>

    {{-- GRID: gap-4 en móvil para ahorrar espacio vertical, gap-8 en escritorio --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">

        {{-- MÓDULO LOGÍSTICA --}}
        <a href="{{ route('logistics.index') }}"
            class="group bg-white p-6 md:p-10 rounded-[30px] md:rounded-[40px] border border-gray-100 shadow-sm hover:shadow-2xl hover:border-reversso transition-all flex flex-col items-start text-left">

            {{-- Badge numérico adaptable --}}
            <div class="w-12 h-12 md:w-16 md:h-16 bg-orange-50 rounded-2xl flex items-center justify-center text-reversso mb-4 md:mb-6 group-hover:scale-110 transition-transform font-black italic text-lg md:text-xl">
                01
            </div>

            <h2 class="text-xl md:text-2xl font-black mb-2 md:mb-3 group-hover:text-reversso tracking-tight transition-colors uppercase italic">
                Logística
            </h2>

            <p class="text-gray-500 text-sm md:text-base leading-relaxed font-medium">
                Gestión de tiempos, viajes y métricas de flota.
            </p>
        </a>

        {{-- MÓDULO INVENTARIO (EN DESARROLLO) --}}
        <div class="bg-gray-50/50 p-6 md:p-10 rounded-[30px] md:rounded-[40px] border border-dashed border-gray-300 opacity-60 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 md:w-16 md:h-16 bg-gray-200 rounded-2xl flex items-center justify-center text-gray-400 mb-4 md:mb-6 font-black italic text-lg md:text-xl">
                    --
                </div>

                <h2 class="text-xl md:text-2xl font-black text-gray-400 mb-2 md:mb-3 tracking-tight uppercase italic">
                    Inventario
                </h2>

                <p class="text-gray-400 text-sm md:text-base leading-relaxed font-medium italic">
                    Módulo en desarrollo...
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
