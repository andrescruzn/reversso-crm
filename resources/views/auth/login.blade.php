<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>Login | Reversso CRM</title>
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
        .bg-reversso { background-color: #E8960C; }
        .text-reversso { color: #E8960C; }
    </style>
</head>

<body class="h-full font-sans antialiased text-gray-900">
    {{-- py-8 en móvil, py-12 en desktop --}}
    <div class="flex min-h-full flex-col justify-center py-8 md:py-12 px-4 sm:px-6 lg:px-8">

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <img src="{{ asset('images/logo.webp') }}" alt="Reversso" class="h-16 md:h-20 mx-auto mb-2">
            <p class="text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] md:tracking-[0.4em] mb-8 md:mb-10">
                CRM LOGÍSTICO
            </p>
        </div>

        <div class="mt-4 sm:mx-auto sm:w-full sm:max-w-[440px]">
            {{-- CARD: Ajuste de padding de px-6 (móvil) a px-10 (desktop) --}}
            <div class="bg-white px-6 py-10 md:px-10 md:py-12 shadow-2xl rounded-[30px] md:rounded-[40px] border border-gray-100 relative overflow-hidden">

                {{-- DECORACIÓN SUPERIOR --}}
                <div class="absolute top-0 left-0 w-full h-2 bg-reversso"></div>

                <header class="mb-8 md:mb-10 text-center">
                    <h2 class="text-xl md:text-2xl font-black text-gray-900 tracking-tight uppercase italic">Acceso Seguro</h2>
                    <p class="text-[10px] md:text-xs font-bold text-gray-400 mt-2 uppercase italic">Ingrese sus credenciales</p>
                </header>

                <form class="space-y-5 md:space-y-6" action="{{ route('login') }}" method="POST">
                    @csrf

                    {{-- EMAIL --}}
                    <div>
                        <label for="email" class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 mb-2 italic">Correo Electrónico</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                class="block w-full rounded-2xl border-gray-100 bg-gray-50/50 py-3 md:py-3.5 px-5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#E8960C] sm:text-sm font-bold transition-all"
                                placeholder="usuario@reversso.com">
                        </div>
                        @error('email')
                        <span class="text-red-500 text-[9px] font-black uppercase mt-2 ml-2 block italic tracking-tighter">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PASSWORD --}}
                    <div>
                        <label for="password" class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 mb-2 italic">Contraseña</label>
                        <div class="mt-1 relative">
                            <input id="password" name="password" type="password" required
                                class="block w-full rounded-2xl border-gray-100 bg-gray-50/50 py-3 md:py-3.5 px-5 pr-12 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-[#E8960C] sm:text-sm font-bold transition-all">
                            <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" tabindex="-1">
                                <svg class="w-5 h-5 eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                <svg class="w-5 h-5 eye-on hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- RECORDARME --}}
                    <div class="flex items-center justify-between px-2">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-reversso focus:ring-reversso transition-all">
                            <label for="remember" class="ml-3 block text-[10px] font-black text-gray-500 uppercase italic tracking-widest">Recordarme</label>
                        </div>
                    </div>

                    {{-- BOTÓN DE ACCIÓN --}}
                    <div class="pt-2 md:pt-4">
                        <button type="submit"
                            class="flex w-full justify-center rounded-2xl bg-gray-900 px-4 py-4 text-xs font-black uppercase tracking-[0.2em] text-white shadow-xl hover:bg-black transition-all active:scale-[0.98]">
                            Ingresar al Sistema
                        </button>
                    </div>
                </form>
            </div>

            {{-- FOOTER --}}
            <p class="mt-8 md:mt-10 text-center text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] italic">
                Reversso S.A.S &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
<script>
function togglePassword(btn) {
    const input = btn.parentElement.querySelector('input');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('.eye-off').classList.toggle('hidden', isHidden);
    btn.querySelector('.eye-on').classList.toggle('hidden', !isHidden);
}
</script>
</body>

</html>
