{{-- resources/views/layouts/crm.blade.php --}}
<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>REVERSSO CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50:  '#fef6e8',
                            100: '#fde9c4',
                            200: '#fbd48e',
                            300: '#f5b94d',
                            400: '#f0a520',
                            500: '#E8960C',
                            600: '#c97d08',
                            700: '#a66306',
                            800: '#7d4b05',
                            900: '#5c3704',
                            950: '#3a2202',
                        },
                    },
                },
            },
        }
    </script>

    <style>
        .text-reversso { color: #E8960C; }
        .bg-reversso { background-color: #E8960C; }
        .border-reversso { border-color: #E8960C; }
        .shadow-reversso { --tw-shadow-color: #E8960C; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        @media (max-width: 768px) {
            .sidebar-closed { transform: translateX(-100%); }
            .sidebar-open { transform: translateX(0); }
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-gray-900 overflow-hidden">
<div class="flex h-screen bg-gray-50">

    {{-- SIDEBAR --}}
    <aside
        id="sidebar"
        class="fixed inset-y-0 left-0 z-[70] w-64 bg-gray-950 flex flex-col flex-shrink-0 shadow-2xl transition-transform duration-300 ease-in-out md:relative md:translate-x-0 sidebar-closed"
    >
        <div class="h-20 flex items-center justify-between px-8">
            <img src="{{ asset('images/logo.webp') }}" alt="Reversso" class="h-10">
            <button onclick="toggleSidebar()" class="md:hidden text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="mt-8 px-4 space-y-2 flex-1">
            <a
                href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'bg-reversso text-white' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all"
            >
                <span class="font-black text-xs uppercase tracking-widest italic">Inicio</span>
            </a>

            {{-- ✅ Logística: Admin observa / Conductor opera --}}
            @if(auth()->user()->hasAnyRole(['Administrador', 'Conductor']))
                <a
                    href="{{ route('logistics.index') }}"
                    class="{{ request()->routeIs('logistics.*') ? 'bg-reversso text-white' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all"
                >
                    <span class="font-black text-xs uppercase tracking-widest italic">Logística</span>
                </a>
            @endif

            {{-- ✅ Asistencia: Conductor ve su propio historial --}}
            @if(auth()->user()->hasRole('Conductor'))
                <a
                    href="{{ route('attendance.my-history') }}"
                    class="{{ request()->routeIs('attendance.my-history') ? 'bg-reversso text-white' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all"
                >
                    <span class="font-black text-xs uppercase tracking-widest italic">Asistencia</span>
                </a>
            @endif

            {{-- ✅ Menú solo Admin --}}
            @if(auth()->user()->hasRole('Administrador'))
                <a
                    href="{{ route('attendance.report') }}"
                    class="{{ request()->routeIs('attendance.report') ? 'bg-reversso text-white' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all text-xs font-black uppercase tracking-widest italic"
                >
                    Asistencia
                </a>

                <a
                    href="{{ route('users.index') }}"
                    class="{{ request()->routeIs('users.*') ? 'bg-reversso text-white' : 'text-white hover:bg-white/10' }} flex items-center px-4 py-3 rounded-2xl transition-all text-xs font-black uppercase tracking-widest italic"
                >
                    Usuarios
                </a>
            @endif
        </nav>

        <div class="p-4 border-t border-white/10 bg-black/5">
            <a href="{{ route('profile.show') }}" class="flex items-center p-3 mb-3 rounded-2xl {{ request()->routeIs('profile.*') ? 'bg-white/20 border-white/20' : 'bg-white/10 border-white/5 hover:bg-white/15' }} border transition-all">
                <div class="h-9 w-9 flex-shrink-0 bg-reversso rounded-xl flex items-center justify-center text-white font-black text-xs uppercase">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="ml-3 overflow-hidden text-white">
                    <p class="text-[11px] font-black uppercase truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[9px] font-bold opacity-50 uppercase">
                        {{ auth()->user()->roles->first()->display_name ?? 'Usuario' }}
                    </p>
                </div>
            </a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button
                    type="submit"
                    class="w-full py-3 bg-white/10 text-white text-[10px] font-black rounded-xl uppercase tracking-widest border border-white/10 hover:bg-red-500/80 transition-all"
                >
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col min-w-0 bg-white overflow-hidden relative">

        {{-- HEADER --}}
        <header class="h-20 border-b border-gray-100 flex items-center justify-between px-4 md:px-10 flex-shrink-0 bg-white relative z-40 shadow-sm">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-gray-600 bg-gray-50 rounded-xl active:scale-95 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] md:tracking-[0.4em] truncate">
                    @yield('module_title', 'Escritorio')
                </h2>
            </div>

            <div class="flex items-center gap-2 md:gap-4">
                @if(session('success'))
                    <div class="hidden lg:flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-100 rounded-full animate-pulse">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-[10px] font-black text-green-600 uppercase italic">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="hidden lg:flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-100 rounded-full">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-[10px] font-black text-red-600 uppercase italic">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="hidden md:flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-full border border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-r border-gray-200 pr-2">
                        {{ now()->translatedFormat('d M') }}
                    </span>
                    <span id="clock-desktop" class="text-[10px] font-black text-reversso uppercase italic min-w-[65px] text-center">
                        --:-- --
                    </span>
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <div class="flex-1 overflow-y-auto p-4 md:p-10 bg-gray-50/30 pb-32 md:pb-10 relative">
            {{-- ✅ OJO: aquí NO renderizamos ninguna tarjeta de asistencia.
                 La asistencia vive SOLO dentro de la vista del conductor (crm/home.blade.php),
                 así evitamos el “duplicado”. --}}
            @yield('content')
        </div>

        {{-- MOBILE FLOATING BAR --}}
        <div class="md:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-auto pointer-events-none">
            <div class="flex items-center gap-3 bg-gray-900/90 backdrop-blur-md px-6 py-3 rounded-full border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.5)] pointer-events-auto">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-r border-white/10 pr-3">
                    {{ now()->translatedFormat('d M') }}
                </span>
                <span id="clock-mobile" class="text-[11px] font-black text-reversso uppercase italic tracking-widest">
                    --:--:--
                </span>
            </div>
        </div>

    </main>
</div>

{{-- ✅ MODAL GLOBAL DE ASISTENCIA: SOLO CONDUCTOR (Admin NO lo ve) --}}
@if(auth()->user()->hasRole('Conductor'))
    <div id="modal-attendance-global" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="toggleGlobalModal('modal-attendance-global')"></div>
        <div class="relative bg-white w-full max-w-sm rounded-[30px] p-8 shadow-2xl transform transition-all scale-100">
            <button onclick="toggleGlobalModal('modal-attendance-global')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <form action="{{ route('attendance.checkin') }}" method="POST">
                @csrf
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 text-reversso">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 uppercase italic tracking-tighter">Iniciar Jornada</h3>
                    <p class="text-xs text-gray-500 mt-2">Registra tu hora de entrada al sistema.</p>
                </div>

                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-6 hover:border-orange-200 transition-colors cursor-pointer group"
                     onclick="document.getElementById('h_check_global').click()">
                    <div class="flex items-center justify-between">
                        <label for="h_check_global" class="text-xs font-black text-gray-700 uppercase italic cursor-pointer group-hover:text-reversso transition-colors">
                            ¿Es día Festivo?
                        </label>
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_holiday" value="1" id="h_check_global"
                                   class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 transition-all checked:border-orange-500 checked:bg-orange-500">
                            <svg class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-gray-900 hover:bg-black text-white font-black py-4 rounded-xl uppercase text-xs tracking-widest active:scale-95 transition-all shadow-lg shadow-gray-200">
                    Confirmar Entrada
                </button>
            </form>
        </div>
    </div>
@endif

{{-- =========================================================  --}}
{{-- MODAL CONFIRMACIÓN GLOBAL (reemplaza confirm() nativo)  --}}
{{-- =========================================================  --}}
<div id="modal-confirm-global" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

    <div class="relative bg-white w-full max-w-sm rounded-[32px] p-8 shadow-2xl animate-in">

        {{-- Icono --}}
        <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>

        {{-- Mensaje --}}
        <p id="confirm-msg" class="text-center text-sm font-bold text-gray-700 leading-relaxed mb-8">
            ¿Estás seguro?
        </p>

        {{-- Botones --}}
        <div class="flex gap-3">
            <button id="confirm-cancel"
                type="button"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-black py-4 rounded-2xl uppercase text-[10px] tracking-widest transition-all">
                Cancelar
            </button>
            <button id="confirm-ok"
                type="button"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-black py-4 rounded-2xl uppercase text-[10px] tracking-widest shadow-lg shadow-red-600/20 transition-all">
                Eliminar
            </button>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('sidebar-closed');
        sidebar.classList.toggle('sidebar-open');
    }

    function toggleGlobalModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.toggle('hidden');
    }

    function updateClock() {
        const now = new Date();

        const timeStr = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });

        const timeShortStr = now.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit', hour12: true
        });

        const desktopClock = document.getElementById('clock-desktop');
        const mobileClock = document.getElementById('clock-mobile');

        if (desktopClock) desktopClock.textContent = timeShortStr;
        if (mobileClock) mobileClock.textContent = timeStr;
    }

    setInterval(updateClock, 1000);
    updateClock();

    // =========================================================
    // SESSION EXPIRATION HANDLER
    // Intercepta errores 419 (CSRF) y 401 en formularios/fetch
    // =========================================================
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // Override fetch para interceptar respuestas
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(function(response) {
                if (response.status === 419 || response.status === 401) {
                    showSessionExpired();
                }
                return response;
            });
        };
    });

    // Interceptar errores en XMLHttpRequest (axios, jquery, etc)
    (function() {
        const origOpen = XMLHttpRequest.prototype.open;
        const origSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function(method, url) {
            this._url = url;
            return origOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function() {
            this.addEventListener('load', function() {
                if (this.status === 419 || this.status === 401) {
                    showSessionExpired();
                }
            });
            return origSend.apply(this, arguments);
        };
    })();

    function showSessionExpired() {
        // Evitar multiples alertas
        if (window._sessionExpiredShown) return;
        window._sessionExpiredShown = true;

        // Crear overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.8)';
        overlay.innerHTML = `
            <div class="bg-white rounded-[30px] p-8 max-w-sm w-full text-center shadow-2xl">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900 uppercase italic tracking-tighter mb-2">Sesion Expirada</h3>
                <p class="text-sm text-gray-500 mb-6">Tu sesion ha expirado por inactividad. Inicia sesion nuevamente.</p>
                <a href="/login" class="inline-block w-full bg-gray-900 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-black transition-all">
                    Ir al Login
                </a>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    function togglePassword(btn) {
        const input = btn.parentElement.querySelector('input');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.querySelector('.eye-off').classList.toggle('hidden', isHidden);
        btn.querySelector('.eye-on').classList.toggle('hidden', !isHidden);
    }

    // =========================================================
    // MODAL DE CONFIRMACIÓN GLOBAL
    // Reemplaza el confirm() nativo del navegador.
    // Uso: <form onsubmit="return false" data-confirm="¿Seguro?">
    //   o desde JS: confirmAction('¿Seguro?', () => form.submit())
    // =========================================================
    (function () {
        const modal   = document.getElementById('modal-confirm-global');
        const msgEl   = document.getElementById('confirm-msg');
        const btnOk   = document.getElementById('confirm-ok');
        const btnCancel = document.getElementById('confirm-cancel');

        let _resolve = null;

        function openConfirm(message) {
            if (!modal) return Promise.resolve(true);
            msgEl.textContent = message;
            modal.classList.remove('hidden');
            btnOk.focus();
            return new Promise(res => { _resolve = res; });
        }

        function close(value) {
            modal.classList.add('hidden');
            if (_resolve) { _resolve(value); _resolve = null; }
        }

        btnOk.addEventListener('click',     () => close(true));
        btnCancel.addEventListener('click', () => close(false));
        modal.addEventListener('click', e => { if (e.target === modal) close(false); });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(false);
        });

        // API global
        window.confirmAction = function (message, onConfirm) {
            openConfirm(message).then(ok => { if (ok && onConfirm) onConfirm(); });
        };

        // Interceptar forms con data-confirm
        document.addEventListener('submit', function (e) {
            const form = e.target;
            const msg  = form.getAttribute('data-confirm');
            if (!msg) return;
            e.preventDefault();
            openConfirm(msg).then(ok => { if (ok) { form.removeAttribute('data-confirm'); form.submit(); } });
        }, true);
    })();
</script>
</body>
</html>
