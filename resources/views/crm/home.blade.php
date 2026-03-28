{{-- resources/views/crm/home.blade.php --}}
@extends('layouts.crm')

@section('module_title', 'Escritorio Inicial')

@section('content')
<div class="max-w-5xl mx-auto px-2 md:px-0">

    {{-- HEADER --}}
    <header class="mb-8 md:mb-10">
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter mb-2 uppercase italic leading-none">
            Bienvenido, <span class="text-reversso">{{ explode(' ', auth()->user()->name)[0] }}</span>
        </h1>
        <p class="text-gray-500 font-bold italic uppercase text-[10px] md:text-xs tracking-widest">
            Seleccione un módulo para operar.
        </p>
    </header>

    {{-- ✅ CONTROL ASISTENCIA (MÓVIL: COMPACTO Y BONITO) --}}
    <section class="mb-8 md:mb-10">
        <div class="relative overflow-hidden rounded-[26px] md:rounded-[44px] shadow-2xl border border-white/10 bg-gradient-to-r from-gray-950 via-gray-900 to-gray-950">

            {{-- Glow suave --}}
            <div class="pointer-events-none absolute -top-24 -left-24 w-72 h-72 bg-reversso/20 blur-3xl rounded-full"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 w-72 h-72 bg-orange-400/10 blur-3xl rounded-full"></div>

            <div class="relative p-5 md:p-10">

                {{-- ✅ TOP: icono + textos (en móvil) --}}
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 md:w-16 md:h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
                        {{-- Icono reloj --}}
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-reversso" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-black tracking-[0.28em] uppercase text-white/50">
                            Control de asistencia
                        </p>

                        @if($activeAttendance)
                            <h2 class="text-xl md:text-3xl font-black uppercase italic text-white tracking-tight leading-none mt-1">
                                Jornada activa
                            </h2>

                            {{-- ✅ META: en móvil apilado / en md inline --}}
                            <div class="mt-2 flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                                <span class="inline-flex items-center gap-2 text-[11px] md:text-xs font-black uppercase tracking-widest text-white/70">
                                    <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                    Iniciada a las {{ \Carbon\Carbon::parse($activeAttendance->check_in)->format('h:i A') }}
                                </span>

                                {{-- ✅ Reloj / contador: compacto en móvil --}}
                                <span class="inline-flex w-fit items-center gap-2 px-3.5 py-2 rounded-2xl bg-white/5 border border-white/10">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-white/50">Tiempo</span>
                                    <span
                                        id="attendance_timer"
                                        class="font-mono text-sm md:text-base font-black text-white tracking-tight"
                                        data-start="{{ \Carbon\Carbon::parse($activeAttendance->check_in)->toIso8601String() }}"
                                    >
                                        00:00:00
                                    </span>
                                </span>
                            </div>
                        @else
                            <h2 class="text-xl md:text-3xl font-black uppercase italic text-white tracking-tight leading-none mt-1">
                                Jornada finalizada
                            </h2>

                            <p class="text-[11px] md:text-xs font-bold uppercase tracking-widest text-white/50 mt-2 italic">
                                Debes iniciar jornada para comenzar.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- ✅ BOTÓN: en móvil SIEMPRE full width, con mejor padding --}}
                <div class="mt-5 md:mt-0 md:absolute md:top-10 md:right-10">
                    @if($activeAttendance)
                        <form action="{{ route('attendance.checkout') }}" method="POST" class="w-full md:w-auto">
                            @csrf
                            <button
                                type="submit"
                                class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white px-6 md:px-8 py-4 md:py-4 rounded-2xl font-black uppercase text-[10px] md:text-xs tracking-widest shadow-xl shadow-red-600/20 transition-all inline-flex items-center justify-center gap-3"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12H3m0 0l3-3m-3 3l3 3" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 3v18" />
                                </svg>
                                Finalizar jornada
                            </button>
                        </form>
                    @else
                        <button
                            type="button"
                            onclick="toggleGlobalModal('modal-attendance-global')"
                            class="w-full md:w-auto bg-white hover:bg-gray-100 text-gray-900 px-6 md:px-8 py-4 rounded-2xl font-black uppercase text-[10px] md:text-xs tracking-widest shadow-xl transition-all inline-flex items-center justify-center gap-3"
                        >
                            <svg class="w-5 h-5 text-reversso" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Marcar entrada
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </section>

    {{-- GRID: MÓDULOS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">

        {{-- MÓDULO LOGÍSTICA --}}
        <a href="{{ route('logistics.index') }}"
           class="group bg-white p-6 md:p-10 rounded-[30px] md:rounded-[40px] border border-gray-100 shadow-sm hover:shadow-2xl hover:border-reversso transition-all flex flex-col items-start text-left">

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

{{-- ✅ Script contador --}}
<script>
(function () {
    const el = document.getElementById('attendance_timer');
    if (!el) return;

    const startIso = el.getAttribute('data-start');
    if (!startIso) return;

    const start = new Date(startIso);

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        const now = new Date();
        let diff = Math.floor((now.getTime() - start.getTime()) / 1000);
        if (diff < 0) diff = 0;

        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;

        el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endsection
