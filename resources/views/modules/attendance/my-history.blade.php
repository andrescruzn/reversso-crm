@extends('layouts.crm')

@section('module_title', 'Mi Asistencia')

@section('content')

@php
    $totalDays    = (int) ($totals->days ?? 0);
    $totalMinutes = (int) ($totals->total_minutes ?? 0);
    $totalH       = (int) floor($totalMinutes / 60);
    $totalM       = $totalMinutes % 60;
    $avgMinutes   = $totalDays > 0 ? (int) round($totalMinutes / $totalDays) : 0;
    $avgH         = (int) floor($avgMinutes / 60);
    $avgM         = $avgMinutes % 60;
@endphp

{{-- ============================================================ --}}
{{-- HEADER --}}
{{-- ============================================================ --}}
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">

    <div>
        <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tighter uppercase italic leading-none">
            Mi <span class="text-reversso">Asistencia</span>
        </h1>
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mt-2 italic">
            Historial personal · {{ $totalDays }} jornadas en el rango
        </p>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
        {{-- FILTROS --}}
        <form action="{{ route('attendance.my-history') }}" method="GET"
              class="flex flex-col sm:flex-row items-center gap-2 bg-white p-2 rounded-[25px] shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Del</span>
                <input type="date" name="from" value="{{ $filters['from'] }}"
                       class="bg-transparent border-none p-0 text-xs font-bold focus:ring-0">
            </div>
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Al</span>
                <input type="date" name="to" value="{{ $filters['to'] }}"
                       class="bg-transparent border-none p-0 text-xs font-bold focus:ring-0">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="w-11 h-11 bg-gray-900 text-white rounded-2xl hover:bg-black transition-all inline-flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <a href="{{ route('attendance.my-history') }}"
                   class="w-11 h-11 bg-gray-100 text-gray-400 rounded-2xl hover:text-red-500 transition-all border border-gray-100 inline-flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </a>
            </div>
        </form>

        {{-- BOTÓN REGISTRAR --}}
        <button
            type="button"
            onclick="toggleGlobalModal('modal-manual-attendance')"
            class="bg-reversso text-white px-6 py-3.5 rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-orange-500/20 hover:scale-[1.02] transition-all inline-flex items-center justify-center gap-2 whitespace-nowrap"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar Jornada Pasada
        </button>
    </div>
</div>

{{-- ============================================================ --}}
{{-- STATS --}}
{{-- ============================================================ --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">

    <div class="bg-white rounded-[28px] p-6 border border-gray-100 shadow-sm">
        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Jornadas</p>
        <p class="text-3xl font-black text-gray-900 tracking-tighter">{{ $totalDays }}</p>
        <p class="text-[9px] text-gray-400 font-bold mt-1 uppercase">En el rango</p>
    </div>

    <div class="bg-white rounded-[28px] p-6 border border-gray-100 shadow-sm">
        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Horas totales</p>
        <div class="flex items-baseline gap-1">
            <span class="text-3xl font-black text-gray-900 tracking-tighter">{{ $totalH }}</span>
            <span class="text-sm font-black text-gray-400">h</span>
            <span class="text-3xl font-black text-gray-900 tracking-tighter ml-1">{{ str_pad($totalM, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="text-sm font-black text-gray-400">m</span>
        </div>
        <p class="text-[9px] text-gray-400 font-bold mt-1 uppercase">Acumuladas</p>
    </div>

    <div class="col-span-2 md:col-span-1 bg-white rounded-[28px] p-6 border border-gray-100 shadow-sm">
        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Promedio diario</p>
        <div class="flex items-baseline gap-1">
            <span class="text-3xl font-black text-gray-900 tracking-tighter">{{ $avgH }}</span>
            <span class="text-sm font-black text-gray-400">h</span>
            <span class="text-3xl font-black text-gray-900 tracking-tighter ml-1">{{ str_pad($avgM, 2, '0', STR_PAD_LEFT) }}</span>
            <span class="text-sm font-black text-gray-400">m</span>
        </div>
        <p class="text-[9px] text-gray-400 font-bold mt-1 uppercase">Por jornada</p>
    </div>
</div>

{{-- ============================================================ --}}
{{-- TABLA --}}
{{-- ============================================================ --}}
<div class="bg-white rounded-[35px] shadow-sm border border-gray-100 overflow-hidden">

    {{-- DESKTOP --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/80 border-b border-gray-100 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                    <th class="px-6 py-5">Fecha</th>
                    <th class="px-6 py-5">Entrada</th>
                    <th class="px-6 py-5">Salida</th>
                    <th class="px-6 py-5">Duración</th>
                    <th class="px-6 py-5 text-center">Tipo</th>
                    <th class="px-6 py-5 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($records as $rec)
                    @php
                        $dur  = \Carbon\Carbon::parse($rec->check_in)->diff(\Carbon\Carbon::parse($rec->check_out));
                        $mins = \Carbon\Carbon::parse($rec->check_in)->diffInMinutes(\Carbon\Carbon::parse($rec->check_out));
                        $isLong = $mins > 540; // más de 9h
                    @endphp
                    <tr class="hover:bg-gray-50/40 transition-colors group">

                        {{-- Fecha --}}
                        <td class="px-6 py-5">
                            <p class="text-xs font-black text-gray-900">
                                {{ \Carbon\Carbon::parse($rec->check_in)->translatedFormat('d M Y') }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">
                                {{ \Carbon\Carbon::parse($rec->check_in)->translatedFormat('l') }}
                            </p>
                        </td>

                        {{-- Entrada --}}
                        <td class="px-6 py-5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 rounded-xl">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                                <span class="text-xs font-black text-green-700">
                                    {{ \Carbon\Carbon::parse($rec->check_in)->format('h:i A') }}
                                </span>
                            </span>
                        </td>

                        {{-- Salida --}}
                        <td class="px-6 py-5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 rounded-xl">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                <span class="text-xs font-black text-red-700">
                                    {{ \Carbon\Carbon::parse($rec->check_out)->format('h:i A') }}
                                </span>
                            </span>
                        </td>

                        {{-- Duración --}}
                        <td class="px-6 py-5">
                            <span class="text-xs font-black {{ $isLong ? 'text-reversso' : 'text-gray-900' }}">
                                {{ $dur->h }}h {{ str_pad($dur->i, 2, '0', STR_PAD_LEFT) }}m
                            </span>
                            @if($isLong)
                                <span class="ml-1 text-[8px] font-black text-reversso uppercase bg-orange-50 px-1.5 py-0.5 rounded-lg">+ext</span>
                            @endif
                        </td>

                        {{-- Tipo --}}
                        <td class="px-6 py-5 text-center">
                            @if($rec->is_holiday)
                                <span class="px-3 py-1 rounded-full text-[8px] font-black uppercase bg-orange-50 text-reversso border border-orange-100">
                                    Festivo
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-[8px] font-black uppercase bg-gray-50 text-gray-400 border border-gray-100">
                                    Normal
                                </span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="px-6 py-5 text-right">
                            <form action="{{ route('attendance.manual.delete', $rec->id) }}" method="POST"
                                  data-confirm="¿Eliminar esta jornada? Las horas extra se recalcularán.">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="opacity-0 group-hover:opacity-100 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all"
                                    title="Eliminar jornada">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center text-gray-300 font-black uppercase text-xs tracking-widest italic">
                            Sin registros en este período
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MOBILE: Cards --}}
    <div class="md:hidden divide-y divide-gray-50">
        @forelse($records as $rec)
            @php
                $dur  = \Carbon\Carbon::parse($rec->check_in)->diff(\Carbon\Carbon::parse($rec->check_out));
                $mins = \Carbon\Carbon::parse($rec->check_in)->diffInMinutes(\Carbon\Carbon::parse($rec->check_out));
                $isLong = $mins > 540;
            @endphp
            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs font-black text-gray-900">
                            {{ \Carbon\Carbon::parse($rec->check_in)->translatedFormat('d M Y') }}
                        </p>
                        <p class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">
                            {{ \Carbon\Carbon::parse($rec->check_in)->translatedFormat('l') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($rec->is_holiday)
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase bg-orange-50 text-reversso border border-orange-100">Festivo</span>
                        @endif
                        <form action="{{ route('attendance.manual.delete', $rec->id) }}" method="POST"
                              data-confirm="¿Eliminar esta jornada? Las horas extra se recalcularán.">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-gray-50 rounded-2xl p-3">
                    <div class="flex-1 text-center">
                        <p class="text-[8px] font-black text-gray-400 uppercase mb-1">Entrada</p>
                        <p class="text-sm font-black text-green-600">{{ \Carbon\Carbon::parse($rec->check_in)->format('h:i A') }}</p>
                    </div>
                    <div class="text-gray-200 font-black">→</div>
                    <div class="flex-1 text-center">
                        <p class="text-[8px] font-black text-gray-400 uppercase mb-1">Salida</p>
                        <p class="text-sm font-black text-red-500">{{ \Carbon\Carbon::parse($rec->check_out)->format('h:i A') }}</p>
                    </div>
                    <div class="w-px bg-gray-200 self-stretch"></div>
                    <div class="flex-1 text-center">
                        <p class="text-[8px] font-black text-gray-400 uppercase mb-1">Duración</p>
                        <p class="text-sm font-black {{ $isLong ? 'text-reversso' : 'text-gray-900' }}">
                            {{ $dur->h }}h {{ str_pad($dur->i, 2, '0', STR_PAD_LEFT) }}m
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-16 text-center text-gray-300 font-black uppercase text-xs tracking-widest italic">
                Sin registros en este período
            </div>
        @endforelse
    </div>

    {{-- PAGINACIÓN --}}
    <div class="px-6 py-5 border-t border-gray-100">
        {{ $records->links() }}
    </div>
</div>

{{-- ============================================================ --}}
{{-- MODAL REGISTRAR JORNADA PASADA --}}
{{-- ============================================================ --}}
<div id="modal-manual-attendance" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="toggleGlobalModal('modal-manual-attendance')"></div>

    <div class="relative bg-white w-full max-w-md rounded-[40px] p-10 shadow-2xl">
        <button onclick="toggleGlobalModal('modal-manual-attendance')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="text-center mb-7">
            <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-reversso" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 uppercase italic tracking-tighter">Jornada Pasada</h3>
            <p class="text-xs text-gray-400 mt-1">Registra una jornada que no pudiste marcar en el momento.</p>
        </div>

        @if($errors->hasBag('manualAttendance') && $errors->manualAttendance->any())
            <div class="mb-5 p-4 bg-red-50 text-red-700 text-[10px] font-black uppercase rounded-2xl">
                @foreach($errors->manualAttendance->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('attendance.manual') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Fecha y hora de entrada</label>
                <input type="datetime-local" name="check_in" value="{{ old('check_in') }}" required
                       class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none">
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-2 tracking-widest">Fecha y hora de salida</label>
                <input type="datetime-local" name="check_out" value="{{ old('check_out') }}" required
                       class="w-full bg-gray-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-reversso outline-none">
            </div>

            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 hover:border-orange-200 transition-colors cursor-pointer group"
                 onclick="document.getElementById('ma_is_holiday').click()">
                <div class="flex items-center justify-between">
                    <label for="ma_is_holiday" class="text-xs font-black text-gray-700 uppercase italic cursor-pointer group-hover:text-reversso transition-colors">
                        ¿Es día Festivo / Dominical?
                    </label>
                    <div class="relative flex items-center">
                        <input type="checkbox" name="is_holiday" value="1" id="ma_is_holiday"
                               {{ old('is_holiday') ? 'checked' : '' }}
                               class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 transition-all checked:border-orange-500 checked:bg-orange-500">
                        <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-gray-900 hover:bg-black text-white font-black py-5 rounded-2xl uppercase text-xs tracking-widest shadow-xl transition-all mt-2">
                Guardar Jornada
            </button>
        </form>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    const openModal = @json(session('open_modal'));
    if (openModal === 'modal-manual-attendance') {
        document.getElementById('modal-manual-attendance')?.classList.remove('hidden');
    }
});
</script>

@endsection
