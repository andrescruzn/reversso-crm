@extends('layouts.crm')

@section('module_title', 'Logística / Reporte de Asistencia')

@section('content')
<div class="max-w-7xl mx-auto px-2 md:px-0">
    {{-- CABECERA --}}
    <div class="mb-8 md:mb-10 flex flex-col md:flex-row justify-between items-start gap-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tighter uppercase italic">Control de Asistencia</h2>
            <p class="text-[10px] md:text-sm text-gray-500 mt-1 md:mt-2 font-medium italic">Histórico consolidado de jornadas laborales del personal.</p>
        </div>

        <a href="{{ route('attendance.export', request()->all()) }}"
            class="w-full md:w-auto bg-gray-900 text-white px-6 py-4 md:py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-lg flex items-center justify-center gap-2 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Exportar Detalle CSV
        </a>
    </div>

    {{-- FILTROS --}}
    <form action="{{ route('attendance.report') }}" method="GET" class="flex flex-col md:flex-row flex-wrap gap-3 mb-8">
        <div class="bg-white border border-gray-100 p-2 rounded-2xl shadow-sm flex items-center pr-4">
            <select name="user_id" onchange="this.form.submit()" class="text-[10px] font-black border-none focus:ring-0 uppercase tracking-widest bg-transparent">
                <option value="">Todos los colaboradores</option>
                @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" {{ $filters['user_id'] == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white border border-gray-100 p-2 rounded-2xl shadow-sm flex items-center gap-2 px-3">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="text-[10px] font-bold border-none p-0 focus:ring-0 text-gray-700">
            <span class="text-gray-300 font-bold">/</span>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="text-[10px] font-bold border-none p-0 focus:ring-0 text-gray-700">
        </div>

        <button type="submit" class="bg-[#ff5a1f] text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 transition-all shadow-lg shadow-orange-100">Filtrar</button>
        <a href="{{ route('attendance.report') }}" class="bg-gray-100 text-gray-500 px-8 py-3 rounded-2xl text-[10px] font-black uppercase text-center tracking-widest hover:bg-gray-200 transition-all">Limpiar Filtros</a>
    </form>

    {{-- TABLA --}}
    <div class="bg-white shadow-sm rounded-[40px] border border-gray-100 overflow-hidden hidden md:block">
        <table class="min-w-full divide-y divide-gray-100">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="px-8 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">Colaborador</th>
                    <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        {{ $filters['user_id'] ? 'Fecha / Detalle' : 'Periodo de Reporte' }}
                    </th>
                    <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">H. Regulares</th>
                    <th class="px-8 py-5 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">H. Extras / Festivas</th>
                    <th class="px-8 py-5 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Tiempo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 bg-white">
                @forelse($attendances as $item)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-8 py-5">
                        <div class="text-sm font-black text-gray-900 uppercase tracking-tighter">{{ $item->user_name }}</div>
                        @if(!$item->is_summary && $item->is_holiday)
                        <span class="inline-block mt-1 text-[8px] bg-[#ff5a1f] text-white px-2 py-0.5 rounded-full font-black uppercase italic tracking-tighter">Festivo</span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center">
                        @if($item->is_summary)
                        <span class="text-[10px] text-gray-400 font-black uppercase">Acumulado del Mes</span>
                        @else
                        <span class="text-xs text-gray-600 font-bold italic">{{ $item->date->format('d/m/Y') }}</span>
                        <span class="block text-[10px] text-gray-400 font-black tracking-tighter uppercase mt-1">{{ $item->check_in }} - {{ $item->check_out }}</span>
                        @endif
                    </td>
                    <td class="px-8 py-5 text-center text-sm font-black text-gray-700">{{ $item->hours_regular }}</td>
                    <td class="px-8 py-5 text-center text-sm font-black text-[#ff5a1f]">{{ $item->hours_extra }}</td>
                    <td class="px-8 py-5 text-right text-sm font-black text-gray-900 italic">{{ $item->total_day }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-8 py-12 text-center text-[10px] font-black text-gray-300 uppercase italic">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- PAGINACIÓN DESKTOP --}}
        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100">
            {{ $attendances->links() }}
        </div>
    </div>

    {{-- MOBILE --}}
    <div class="md:hidden space-y-4 mb-10">
        @foreach($attendances as $item)
        <div class="bg-white p-6 rounded-[30px] border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Colaborador</p>
                    <h3 class="text-sm font-black text-gray-900 uppercase italic">{{ $item->user_name }}</h3>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Periodo</p>
                    <p class="text-xs font-bold text-gray-600 italic">{{ $item->date ? $item->date->format('d/m/Y') : 'Consolidado' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-4 border-t border-gray-50">
                <div>
                    <p class="text-[8px] font-black text-gray-400 uppercase">Reg.</p>
                    <p class="text-xs font-black text-gray-700">{{ $item->hours_regular }}</p>
                </div>
                <div>
                    <p class="text-[8px] font-black text-[#ff5a1f] uppercase">Extra</p>
                    <p class="text-xs font-black text-[#ff5a1f]">{{ $item->hours_extra }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] font-black text-gray-900 uppercase">Total</p>
                    <p class="text-sm font-black text-gray-900 italic">{{ $item->total_day }}</p>
                </div>
            </div>
        </div>
        @endforeach

        {{-- PAGINACIÓN MOBILE --}}
        <div class="pt-2 pb-10">
            {{ $attendances->links() }}
        </div>
    </div>
</div>

<style>
    /* Estilos para el paginador de Tailwind */
    .pagination nav svg {
        width: 20px;
    }

    .pagination nav span,
    .pagination nav a {
        border-radius: 12px !important;
        font-weight: 900 !important;
        font-size: 10px !important;
        text-transform: uppercase !important;
        margin: 0 2px !important;
    }

    .pagination nav .bg-indigo-600 {
        background-color: #ff5a1f !important;
        border-color: #ff5a1f !important;
    }
</style>
@endsection
