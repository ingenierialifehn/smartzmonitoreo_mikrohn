@extends('layouts.app')

@section('title', 'Acceso NOC | Smartz Monitoreo MikroHN')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-slate-50 dark:bg-gradient-to-br dark:from-noc-950 dark:via-noc-900 dark:to-noc-950 transition-colors duration-200"
     x-data="{
        usuario: '{{ old('usuario', '') }}',
        password: '',
        remember: true,
        loading: false,
        serverError: '',
        theme: localStorage.getItem('smartz_theme') || 'light',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('smartz_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        async submitLogin() {
            if (!this.usuario || !this.password) {
                window.nocToast({
                    type: 'warning',
                    title: 'Campos requeridos',
                    message: 'Ingrese su usuario o correo y contraseña para acceder al NOC.'
                });
                return;
            }
            this.loading = true;
            this.serverError = '';

            try {
                const csrfMeta = document.querySelector('meta[name=csrf-token]');
                const csrfInput = document.querySelector('input[name=_token]');
                const token = csrfMeta ? csrfMeta.content : (csrfInput ? csrfInput.value : '');

                // Ruta relativa estricta /login: previene Mixed Content y bloqueos CORS en HTTPS (Render)
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        usuario: this.usuario,
                        password: this.password,
                        remember: this.remember
                    })
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (parseErr) {
                    console.warn('Respuesta no JSON recibida del servidor:', parseErr);
                }

                if (response.ok && data && data.success) {
                    // Redirección inmediata a dashboard
                    window.location.href = data.redirect || '/dashboard';
                    return;
                } else {
                    let errorMsg = data ? data.message : null;
                    if (!errorMsg) {
                        if (response.status === 500) {
                            errorMsg = 'Error interno del servidor (500). Verifique las credenciales de Base de Datos MySQL en Render.';
                        } else if (response.status === 419) {
                            errorMsg = 'El token de seguridad CSRF ha expirado. Por favor recargue la página.';
                        } else if (response.status === 404) {
                            errorMsg = 'Ruta de autenticación no encontrada (404).';
                        } else {
                            errorMsg = 'Credenciales inválidas en base de datos MikroHN.';
                        }
                    }

                    this.serverError = errorMsg;
                    window.nocToast({
                        type: 'error',
                        title: response.status >= 500 ? 'Error de Servidor / Base de Datos' : 'Fallo de Autenticación',
                        message: errorMsg
                    });
                    this.loading = false;
                }
            } catch (err) {
                console.error('Fetch error:', err);
                this.serverError = 'No fue posible comunicar con el servidor por AJAX. Intentando envío tradicional directo...';
                window.nocToast({
                    type: 'error',
                    title: 'Fallo de Conexión Asíncrona',
                    message: 'Reintentando autenticación mediante envío de formulario estándar...'
                });

                // Fallback automático nativo por formulario
                setTimeout(() => {
                    if (this.$refs.loginForm) {
                        this.$refs.loginForm.submit();
                    } else {
                        this.loading = false;
                    }
                }, 800);
            }
        }
     }">

    <!-- Top Floating Theme Toggle -->
    <div class="absolute top-4 right-4 z-20">
        <button @click="toggleTheme()" 
                :title="theme === 'dark' ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                class="w-10 h-10 rounded-xl flex items-center justify-center border transition-all text-sm bg-white dark:bg-noc-850 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-amber-400 shadow-sm hover:shadow">
            <i class="fa-solid" :class="theme === 'dark' ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600'"></i>
        </button>
    </div>

    <!-- Background Tech Grid Pattern -->
    <div class="absolute inset-0 opacity-10 dark:opacity-15 pointer-events-none" 
         style="background-image: radial-gradient(rgba(6, 182, 212, 0.3) 1px, transparent 1px), radial-gradient(rgba(0, 255, 157, 0.25) 1px, transparent 1px); background-size: 36px 36px; background-position: 0 0, 18px 18px;">
    </div>

    <!-- Glowing ambient circles -->
    <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-cyan-400/10 dark:bg-noc-cyan/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-emerald-400/10 dark:bg-noc-neon/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white dark:bg-gradient-to-br dark:from-noc-850 dark:to-noc-900 border border-slate-200 dark:border-noc-cyan/30 shadow-md dark:shadow-neon-cyan mb-4 relative">
                <i class="fa-solid fa-tower-broadcast text-cyan-600 dark:text-noc-cyan text-2xl animate-pulse"></i>
                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center justify-center gap-2">
                SMARTZ <span class="text-cyan-600 dark:text-noc-cyan">NOC</span>
            </h1>
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Centro de Monitoreo MikroTik en Tiempo Real
            </p>

            <!-- Status Indicator -->
            <div class="inline-flex items-center gap-2 mt-4 px-3 py-1 rounded-full text-xs font-mono bg-white dark:bg-noc-900/90 border border-slate-200 dark:border-slate-700/60 text-slate-700 dark:text-slate-300 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500 dark:bg-noc-neon animate-pulse"></span>
                <span>Motor: <span class="text-cyan-700 dark:text-noc-neon font-semibold">Independiente</span></span>
            </div>
        </div>

        <!-- Login Card -->
        <div class="noc-card p-8 shadow-xl relative bg-white dark:bg-noc-850/75 border border-slate-200 dark:border-white/10 rounded-2xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-wide">Acceso de Operador NOC</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Use sus credenciales de usuario registrado</p>
            </div>

            <!-- Alerta de Errores de Validación Nativos de Laravel -->
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-400 text-xs flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 mt-0.5 text-sm flex-shrink-0"></i>
                    <div>
                        <div class="font-bold mb-0.5">Fallo de Autenticación / Conexión</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Mensaje reactivo de error asíncrono o base de datos -->
            <div x-show="serverError" x-cloak class="mb-5 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 mt-0.5 text-sm flex-shrink-0"></i>
                <div x-text="serverError"></div>
            </div>

            <form x-ref="loginForm"
                  method="POST"
                  action="{{ route('login.post') }}"
                  @submit.prevent="submitLogin()"
                  class="space-y-5">
                @csrf

                <!-- Field: Usuario / Email -->
                <div>
                    <label for="usuario" class="block text-xs font-mono text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-user-shield text-cyan-600 dark:text-noc-cyan mr-1.5"></i> Usuario o Correo
                    </label>
                    <div class="relative">
                        <input id="usuario"
                               type="text"
                               name="usuario"
                               x-model="usuario"
                               value="{{ old('usuario') }}"
                               autofocus
                               required
                               placeholder="Ej: admin o usuario@red.hn"
                               class="w-full bg-slate-50 dark:bg-noc-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-4 py-3 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-sans focus:outline-none focus:border-cyan-500 dark:focus:border-noc-cyan focus:ring-1 focus:ring-cyan-500 dark:focus:ring-noc-cyan transition-all text-sm">
                    </div>
                </div>

                <!-- Field: Password -->
                <div>
                    <label for="password" class="block text-xs font-mono text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-key text-emerald-600 dark:text-noc-neon mr-1.5"></i> Contraseña
                    </label>
                    <div class="relative">
                        <input id="password"
                               type="password"
                               name="password"
                               x-model="password"
                               required
                               placeholder="••••••••••••"
                               class="w-full bg-slate-50 dark:bg-noc-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-4 py-3 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-sans focus:outline-none focus:border-emerald-500 dark:focus:border-noc-neon focus:ring-1 focus:ring-emerald-500 dark:focus:ring-noc-neon transition-all text-sm">
                    </div>
                </div>

                <!-- Remember Me & Info -->
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox"
                               name="remember"
                               value="1"
                               x-model="remember"
                               class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-noc-900 text-cyan-600 dark:text-noc-cyan focus:ring-0">
                        <span>Recordar sesión</span>
                    </label>
                    <span class="font-mono text-[11px] text-slate-500">Bcrypt v12</span>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        :disabled="loading"
                        class="w-full relative group overflow-hidden bg-gradient-to-r from-cyan-600 via-teal-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold py-3.5 px-6 rounded-xl transition-all duration-200 transform active:scale-98 shadow-md hover:shadow-lg flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading" class="flex items-center gap-2 text-sm tracking-wider uppercase font-extrabold">
                        <i class="fa-solid fa-bolt-lightning"></i> Iniciar Monitoreo
                    </span>
                    <span x-show="loading" x-cloak class="flex items-center gap-2 text-sm tracking-wider uppercase font-extrabold">
                        <i class="fa-solid fa-circle-notch fa-spin"></i> Autenticando...
                    </span>
                </button>
            </form>

            <!-- Bottom Disclaimer -->
            <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800 text-center">
                <p class="text-xs text-slate-500 font-mono">
                    <i class="fa-solid fa-lock text-slate-400 mr-1"></i> Aislamiento Total • Base de Datos MySQL Local
                </p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-500 dark:text-slate-500 mt-6 font-mono">
            SMARTZ MONITOREO NOC &copy; 2026 • Motor Independiente
        </p>
    </div>
</div>
@endsection
