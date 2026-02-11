<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
    <title>Login | Reversso CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-reversso {
            background-color: #FF6B00;
        }

        .text-reversso {
            color: #FF6B00;
        }

        /* Mejora del focus para que coincida con la marca */
        .focus-ring-reversso:focus {
            outline: none;
            ring: 2px;
            ring-color: #FF6B00;
            border-color: #FF6B00;
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-gray-900">
    {{-- py-8 en móvil, py-12 en desktop --}}
    <div class="flex min-h-full flex-col justify-center py-8 md:py-12 px-4 sm:px-6 lg:px-8">

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            {{-- LOGO RESPONSIVE: text-4xl en móvil, text-5xl en desktop --}}
            <h1 class="text-4xl md:text-5xl font-black text-gray-900 italic tracking-tighter uppercase mb-2">
                REVE<span class="text-reversso">RSSO</span>
            </h1>
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
                                class="block w-full rounded-2xl border-gray-100 bg-gray-50/50 py-3 md:py-3.5 px-5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#FF6B00] sm:text-sm font-bold transition-all"
                                placeholder="usuario@reversso.com">
                        </div>
                        @error('email')
                        <span class="text-red-500 text-[9px] font-black uppercase mt-2 ml-2 block italic tracking-tighter">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PASSWORD --}}
                    <div>
                        <label for="password" class="block text-[9px] md:text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 mb-2 italic">Contraseña</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                class="block w-full rounded-2xl border-gray-100 bg-gray-50/50 py-3 md:py-3.5 px-5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-[#FF6B00] sm:text-sm font-bold transition-all">
                        </div>
                    </div>

                    {{-- RECORDARME --}}
                    <div class="flex items-center justify-between px-2">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-reversso focus:ring-reversso transition-all">
                            <label for="remember-me" class="ml-3 block text-[10px] font-black text-gray-500 uppercase italic tracking-widest">Recordarme</label>
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
</body>

</html>
