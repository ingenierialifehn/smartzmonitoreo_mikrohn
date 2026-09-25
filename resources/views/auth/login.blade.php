@extends('layouts.app')

@section('title', 'Acceso NOC | Smartz Monitoreo MikroHN')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-slate-50 dark:bg-gradient-to-br dark:from-noc-950 dark:via-noc-900 dark:to-noc-950 transition-colors duration-200"
     x-data="{
        usuario: '',
        password: '',
        remember: true,
        loading: false,
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
            try {
                const response = await fetch('{{ route('login.post') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        usuario: this.usuario,
                        password: this.password,
                        remember: this.remember
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Redirección inmediata sin esperas artificiales
                    window.location.href = data.redirect || '{{ route('dashboard') }}';
                    return;
                } else {
                    window.nocToast({
                        type: 'error',
                        title: 'Fallo de Autenticación',
                        message: data.message || 'Credenciales inválidas en base de datos MikroHN.'
                    });
                    this.loading = false;
                }
            } catch (err) {
                console.error(err);
                window.nocToast({
                    type: 'error',
                    title: 'Error de Red / Servidor',
                    message: 'No fue posible comunicar con el servidor de autenticación.'
                });
                this.loading = false;
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

            <form @submit.prevent="submitLogin()" class="space-y-5">
                <!-- Field: Usuario / Email -->
                <div>
                    <label class="block text-xs font-mono text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-user-shield text-cyan-600 dark:text-noc-cyan mr-1.5"></i> Usuario o Correo
                    </label>
                    <div class="relative">
                        <input type="text"
                               x-model="usuario"
                               autofocus
                               required
                               placeholder="Ej: admin o usuario@red.hn"
                               class="w-full bg-slate-50 dark:bg-noc-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-4 py-3 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-sans focus:outline-none focus:border-cyan-500 dark:focus:border-noc-cyan focus:ring-1 focus:ring-cyan-500 dark:focus:ring-noc-cyan transition-all text-sm">
                    </div>
                </div>

                <!-- Field: Password -->
                <div>
                    <label class="block text-xs font-mono text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-key text-emerald-600 dark:text-noc-neon mr-1.5"></i> Contraseña
                    </label>
                    <div class="relative">
                        <input type="password"
                               x-model="password"
                               required
                               placeholder="••••••••••••"
                               class="w-full bg-slate-50 dark:bg-noc-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-4 py-3 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-sans focus:outline-none focus:border-emerald-500 dark:focus:border-noc-neon focus:ring-1 focus:ring-emerald-500 dark:focus:ring-noc-neon transition-all text-sm">
                    </div>
                </div>

                <!-- Remember Me & Info -->
                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="remember" class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-noc-900 text-cyan-600 dark:text-noc-cyan focus:ring-0">
                        <span>Recordar sesión</span>
                    </label>
                    <span class="font-mono text-[11px] text-slate-500">Bcrypt v12</span>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        :disabled="loading"
                        class="w-full relative group overflow-hidden bg-gradient-to-r from-cyan-600 via-teal-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold py-3.5 px-6 rounded-xl transition-all duration-200 transform active:scale-98 shadow-md hover:shadow-lg flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!loading">
                        <span class="flex items-center gap-2 text-sm tracking-wider uppercase font-extrabold">
                            <i class="fa-solid fa-bolt-lightning"></i> Iniciar Monitoreo
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2 text-sm tracking-wider uppercase font-extrabold">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> Autenticando...
                        </span>
                    </template>
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
