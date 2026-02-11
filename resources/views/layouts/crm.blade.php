<!DOCTYPE html>
<html lang="es" class="h-full bg-white">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>REVERSSO CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-reversso {
            color: #FF6B00;
        }

        .bg-reversso {
            background-color: #FF6B00;
        }

        .border-reversso {
            border-color: #FF6B00;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .sidebar-closed {
                transform: translateX(-100%);
            }

            .sidebar-open {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-gray-900 overflow-hidden">
    <div class="flex h-screen bg-gray-50">

        {{-- SIDEBAR RESPONSIVE --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-[70] w-64 bg-reversso flex flex-col flex-shrink-0 shadow-2xl transition-transform duration-300 ease-in-out md:relative md:translate-x-0 sidebar-closed">
            <div class="h-20 flex items-center justify-between px-8">
                <span class="text-2xl font-black italic tracking-tighter text-white">REVERSSO</span>
                <button onclick="toggleSidebar()" class="md:hidden text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="mt-8 px-4 space-y-2 flex-1">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-white text-reversso' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all">
                    <span class="font-black text-xs uppercase tracking-widest italic">Inicio</span>
                </a>
                <a href="{{ route('logistics.index') }}" class="{{ request()->routeIs('logistics.*') ? 'bg-white text-reversso' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all">
                    <span class="font-black text-xs uppercase tracking-widest italic">Logística</span>
                </a>
                @if(auth()->user()->hasRole('Administrador'))
                <a href="{{ route('attendance.report') }}" class="{{ request()->routeIs('attendance.*') ? 'bg-white text-reversso' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all text-xs font-black uppercase tracking-widest italic">Asistencia</a>
                @endif
            </nav>

            <div class="p-4 border-t border-white/10 bg-black/5">
                <div class="flex items-center p-3 mb-4 rounded-2xl bg-white/10 border border-white/5">
                    <div class="h-9 w-9 flex-shrink-0 bg-white rounded-xl flex items-center justify-center text-reversso font-black text-xs uppercase">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="ml-3 overflow-hidden text-white">
                        <p class="text-[11px] font-black uppercase truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[9px] font-bold opacity-50 uppercase">{{ auth()->user()->roles->first()->display_name ?? 'Usuario' }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-orange-700/40 text-white text-[10px] font-black rounded-xl uppercase tracking-widest border border-white/10">Cerrar Sesión</button>
                </form>
            </div>
        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <main class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden">

            {{-- HEADER --}}
            <header class="h-20 border-b border-gray-100 flex items-center justify-between px-4 md:px-10 flex-shrink-0 bg-white relative z-40">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="md:hidden p-2 text-gray-600 bg-gray-50 rounded-xl active:scale-95 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] md:tracking-[0.4em] truncate">@yield('module_title', 'Escritorio')</h2>
                </div>

                <div class="flex items-center gap-2 md:gap-4">
                    @if(session('success'))
                    <span class="hidden lg:inline-block text-[10px] font-black text-green-500 uppercase bg-green-50 px-4 py-2 rounded-full border border-green-100 italic">✓ {{ session('success') }}</span>
                    @endif

                    {{-- INDICADOR FECHA/HORA DESKTOP --}}
                    <div class="hidden md:flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-full border border-gray-100">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-r border-gray-200 pr-2">{{ now()->translatedFormat('d M') }}</span>
                        <span id="clock-desktop" class="text-[10px] font-black text-reversso uppercase italic min-w-[65px]">--:-- --</span>
                    </div>
                </div>
            </header>

            {{-- ZONA DE CONTENIDO --}}
            <div class="flex-1 overflow-y-auto p-4 md:p-10 bg-gray-50/30 pb-32 md:pb-10">

                @if(request()->routeIs('dashboard') && !auth()->user()->hasRole('Administrador'))
                <div class="max-w-4xl mb-8 p-6 bg-gray-900 rounded-[30px] shadow-2xl text-white relative overflow-hidden border-b-4 border-orange-600">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-6 relative z-10">
                        <div class="flex items-center gap-5 w-full lg:w-auto">
                            <div class="h-12 w-12 bg-white/10 rounded-2xl flex-shrink-0 flex items-center justify-center text-orange-500 font-black italic">!</div>
                            <div>
                                <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Reloj de Personal</p>
                                <h3 class="text-lg md:text-xl font-black italic uppercase tracking-tighter">
                                    {{ empty($activeAttendance) ? 'Jornada no iniciada' : 'Sesión Activa' }}
                                </h3>
                                @if(!empty($activeAttendance))
                                <p class="text-gray-400 text-[10px] font-bold italic">Desde: {{ \Carbon\Carbon::parse($activeAttendance->check_in)->format('h:i A') }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="w-full lg:w-auto">
                            @if(!empty($activeAttendance))
                            <form action="{{ route('attendance.checkout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="w-full lg:w-auto bg-red-500 hover:bg-red-600 px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest text-white transition-all shadow-lg">Finalizar Jornada</button>
                            </form>
                            @else
                            <button type="button" onclick="toggleGlobalModal('modal-attendance-global')" class="w-full lg:w-auto bg-orange-500 hover:bg-orange-600 px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest text-white transition-all">Marcar Entrada</button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @yield('content')
            </div>

            {{-- BARRA FLOTANTE INFERIOR (SOLO MOBILE) --}}
            <div class="md:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-auto">
                <div class="flex items-center gap-3 bg-gray-900/90 backdrop-blur-md px-6 py-3 rounded-full border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-r border-white/10 pr-3">{{ now()->translatedFormat('d M') }}</span>
                    <span id="clock-mobile" class="text-[11px] font-black text-reversso uppercase italic tracking-widest">--:--:--</span>
                </div>
            </div>

        </main>
    </div>

    {{-- MODAL GLOBAL --}}
    <div id="modal-attendance-global" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="toggleGlobalModal('modal-attendance-global')"></div>
        <div class="relative bg-white w-full max-w-sm rounded-[30px] p-8 md:p-10 shadow-2xl">
            <form action="{{ route('attendance.checkin') }}" method="POST">
                @csrf
                <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase italic tracking-tighter text-center">Iniciar Jornada</h3>
                <div class="p-4 bg-orange-50 rounded-2xl border border-orange-100 mb-6">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_holiday" value="1" id="h_check_global" class="w-6 h-6 text-reversso rounded-lg border-orange-200">
                        <label for="h_check_global" class="text-xs font-black text-reversso uppercase italic cursor-pointer">¿Día Festivo?</label>
                    </div>
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white font-black py-4 rounded-xl uppercase text-xs tracking-widest active:scale-95 transition-all">Confirmar Entrada</button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-closed');
            sidebar.classList.toggle('sidebar-open');
        }

        function toggleGlobalModal(id) {
            document.getElementById(id)?.classList.toggle('hidden');
        }

        // LÓGICA DEL RELOJ EN TIEMPO REAL
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            const timeShortStr = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            if (document.getElementById('clock-desktop')) {
                document.getElementById('clock-desktop').textContent = timeShortStr;
            }
            if (document.getElementById('clock-mobile')) {
                document.getElementById('clock-mobile').textContent = timeStr;
            }
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>

</html>
