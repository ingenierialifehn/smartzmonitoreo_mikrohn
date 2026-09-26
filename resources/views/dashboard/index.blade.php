@extends('layouts.app')

@section('title', 'Smartz Monitoreo NOC | MikroTik Core')

@section('content')
<div x-data="nocDashboard()" x-init="initDashboard()" class="flex flex-col min-h-screen">

    <!-- Top Navigation Header (Full Width Responsive) -->
    <header class="bg-white/95 dark:bg-noc-900/90 border-b border-slate-200 dark:border-slate-800/80 backdrop-blur-md sticky top-0 z-50 py-3.5 transition-colors duration-150">
        <div class="w-full px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4">
            
            <!-- Left: Brand & Radar Status -->
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gradient-to-tr dark:from-noc-850 dark:to-noc-800 border border-slate-200 dark:border-noc-cyan/40 flex items-center justify-center shadow-sm dark:shadow-neon-cyan relative">
                    <i class="fa-solid fa-satellite-dish text-cyan-600 dark:text-noc-cyan text-lg"></i>
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-extrabold tracking-wider text-slate-900 dark:text-white">SMARTZ <span class="text-cyan-600 dark:text-noc-cyan">NOC</span></span>
                        <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-noc-800 text-cyan-700 dark:text-noc-cyan border border-slate-200 dark:border-noc-cyan/30">v2.0 Core</span>
                    </div>
                    <span class="text-[10px] font-bold tracking-wider flex items-center gap-1.5"
                          :class="routerOnline ? 'text-emerald-500' : 'text-rose-500'">
                        <span class="w-2 h-2 rounded-full" :class="routerOnline ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                        <span x-text="routerOnline ? 'ENLACE CONECTADO' : 'ENLACE DESCONECTADO'"></span>
                    </span>
                </div>
            </div>

            <!-- Center: Router Selector & Quick Status -->
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-100 dark:bg-noc-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl p-1 shadow-inner">
                    <span class="px-2.5 text-slate-500 dark:text-slate-400 text-xs font-mono uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-server text-cyan-600 dark:text-noc-cyan"></i> Router:
                    </span>
                    <select x-model="selectedRouterId" 
                            @change="changeRouter()"
                            class="bg-white dark:bg-noc-900 border-0 text-slate-800 dark:text-white font-mono text-xs font-semibold rounded-lg px-3 py-1.5 focus:ring-1 focus:ring-cyan-500 dark:focus:ring-noc-cyan focus:outline-none cursor-pointer shadow-sm">
                        <template x-for="r in routersList" :key="r.id">
                            <option :value="r.id" x-text="`${r.nombre} (${r.ip})`"></option>
                        </template>
                    </select>
                </div>

                <!-- Latency Badge -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800 font-mono text-xs shadow-sm">
                    <span class="text-slate-500 dark:text-slate-400">Ping:</span>
                    <span :class="pingMs !== null ? (pingMs < 50 ? 'text-emerald-600 dark:text-noc-neon' : (pingMs < 120 ? 'text-amber-600 dark:text-noc-amber' : 'text-red-600 dark:text-noc-danger')) : 'text-slate-400'" 
                          class="font-bold flex items-center gap-1">
                        <i class="fa-solid fa-wave-square text-[10px]"></i>
                        <span x-text="pingMs !== null ? `${pingMs} ms` : '-- ms'"></span>
                    </span>
                </div>
            </div>

            <!-- Right: NOC Clock, Controls & User Profile -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <!-- Clock -->
                <div class="hidden md:flex flex-col text-right font-mono">
                    <span class="text-xs font-bold text-slate-900 dark:text-white tracking-widest" x-text="localClock">--:--:--</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider">Honduras (NOC Time)</span>
                </div>

                <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden md:block"></div>

                <!-- Theme Toggle Sol ☀️ / Luna 🌙 -->
                <button @click="toggleTheme()" 
                        :title="theme === 'dark' ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border transition-all text-xs shadow-sm"
                        :class="theme === 'dark' ? 'bg-noc-850 border-slate-700 text-amber-400 hover:border-amber-400' : 'bg-white border-slate-200 text-amber-500 hover:border-slate-300'">
                    <i class="fa-solid" :class="theme === 'dark' ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600'"></i>
                </button>

                <!-- Quick Sound Toggle -->
                <button @click="toggleSound()" 
                        :title="soundEnabled ? 'Silenciar Alertas' : 'Activar Alertas Sonoras'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border transition-all text-xs shadow-sm"
                        :class="soundEnabled ? 'bg-white dark:bg-noc-850 border-slate-200 dark:border-slate-700 text-cyan-600 dark:text-noc-cyan hover:border-cyan-400' : 'bg-slate-100 dark:bg-noc-950 border-slate-200 dark:border-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'">
                    <i class="fa-solid" :class="soundEnabled ? 'fa-volume-high' : 'fa-volume-xmark'"></i>
                </button>

                <!-- Polling Pause / Play -->
                <button @click="togglePolling()" 
                        :title="pollingPaused ? 'Reanudar Sondeo' : 'Pausar Sondeo'"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border transition-all text-xs shadow-sm"
                        :class="!pollingPaused ? 'bg-white dark:bg-noc-850 border-emerald-300 dark:border-noc-neon/40 text-emerald-600 dark:text-noc-neon' : 'bg-slate-100 dark:bg-noc-950 border-amber-300 dark:border-noc-amber/40 text-amber-600 dark:text-noc-amber'">
                    <i class="fa-solid" :class="!pollingPaused ? 'fa-pause' : 'fa-play'"></i>
                </button>

                <!-- Refresh Button -->
                <button @click="fetchMetrics(true)" 
                        :disabled="loading"
                        title="Actualizar ahora"
                        class="w-9 h-9 rounded-xl bg-white dark:bg-noc-850 border border-slate-200 dark:border-slate-700 hover:border-cyan-500 dark:hover:border-noc-cyan text-slate-600 dark:text-slate-300 hover:text-cyan-600 dark:hover:text-noc-cyan flex items-center justify-center transition-all text-xs disabled:opacity-50 shadow-sm">
                    <i class="fa-solid fa-arrows-rotate" :class="loading ? 'fa-spin text-cyan-600 dark:text-noc-cyan' : ''"></i>
                </button>

                <!-- 🔔 Centro de Notificaciones / Campana NOC (Dropdown) -->
                <div class="relative" @click.away="notifDropdownOpen = false">
                    <button @click="notifDropdownOpen = !notifDropdownOpen"
                            title="Centro de Notificaciones e Incidentes NOC"
                            class="relative w-9 h-9 rounded-xl flex items-center justify-center border transition-all text-xs shadow-sm bg-white dark:bg-noc-850 border-slate-200 dark:border-slate-700 hover:border-cyan-500 dark:hover:border-noc-cyan text-slate-600 dark:text-slate-300 hover:text-cyan-600 dark:hover:text-noc-cyan">
                        <i class="fa-solid fa-bell text-sm" :class="alertasHistorial.length > 0 ? 'text-amber-500 animate-pulse' : ''"></i>
                        
                        <!-- Badge con el conteo de incidentes no leídos -->
                        <span x-show="alertasHistorial.length > 0"
                              x-cloak
                              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-600 dark:bg-noc-danger text-white text-[10px] font-bold font-mono rounded-full flex items-center justify-center shadow-sm">
                            <span x-text="alertasHistorial.length"></span>
                        </span>
                    </button>

                    <!-- Dropdown Menú Emergente -->
                    <div x-show="notifDropdownOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-noc-900 border border-slate-200 dark:border-slate-700/80 shadow-2xl z-50 overflow-hidden font-sans">
                        
                        <!-- Cabecera del Dropdown -->
                        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-noc-950/80">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-bell text-cyan-600 dark:text-noc-cyan text-xs"></i>
                                <span class="text-xs font-bold font-mono uppercase tracking-wider text-slate-800 dark:text-white">Alertas NOC</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-noc-cyan border border-cyan-300 dark:border-noc-cyan/40"
                                      x-text="`${alertasHistorial.length} eventos`"></span>
                            </div>
                            <button x-show="alertasHistorial.length > 0"
                                    @click="limpiarNotificaciones()"
                                    class="text-[11px] font-mono font-medium text-red-500 hover:text-red-700 dark:text-noc-danger dark:hover:text-red-400 hover:underline flex items-center gap-1 cursor-pointer">
                                <i class="fa-solid fa-trash-can text-[10px]"></i> Limpiar notificaciones
                            </button>
                        </div>

                        <!-- Lista cronológica de alertas -->
                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/80">
                            <template x-if="alertasHistorial.length === 0">
                                <div class="py-8 px-4 text-center">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-noc-800 flex items-center justify-center mx-auto mb-2 text-slate-400 dark:text-slate-500">
                                        <i class="fa-solid fa-circle-check text-emerald-500 dark:text-noc-neon text-base"></i>
                                    </div>
                                    <p class="text-xs font-medium text-slate-600 dark:text-slate-300">Sin incidentes registrados</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">El sistema opera con normalidad</p>
                                </div>
                            </template>

                            <template x-for="alerta in alertasHistorial" :key="alerta.id">
                                <div class="p-3.5 hover:bg-slate-50/80 dark:hover:bg-noc-850/50 transition-colors flex items-start gap-3">
                                    <!-- Icono según tipo -->
                                    <div class="mt-0.5 flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-xs"
                                         :class="{
                                             'bg-red-100 dark:bg-red-950 text-red-600 dark:text-noc-danger border border-red-200 dark:border-red-900/60': alerta.tipo === 'error',
                                             'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-noc-neon border border-emerald-200 dark:border-emerald-900/60': alerta.tipo === 'success',
                                             'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-noc-amber border border-amber-200 dark:border-amber-900/60': alerta.tipo === 'warning',
                                             'bg-cyan-100 dark:bg-cyan-950 text-cyan-600 dark:text-noc-cyan border border-cyan-200 dark:border-cyan-900/60': alerta.tipo === 'info'
                                         }">
                                        <i class="fa-solid" :class="{
                                            'fa-triangle-exclamation': alerta.tipo === 'error',
                                            'fa-circle-check': alerta.tipo === 'success',
                                            'fa-bolt': alerta.tipo === 'warning',
                                            'fa-circle-info': alerta.tipo === 'info'
                                        }"></i>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="alerta.titulo"></span>
                                            <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500 whitespace-nowrap" x-text="alerta.hora"></span>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-sans break-words" x-text="alerta.mensaje"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown & Logout -->
                <div class="flex items-center gap-2 pl-1 sm:pl-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-cyan-600 to-emerald-600 flex items-center justify-center text-xs font-bold text-white shadow">
                        {{ strtoupper(substr($user->name ?? 'OP', 0, 2)) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" 
                                title="Cerrar sesión"
                                class="w-8 h-8 rounded-lg bg-white dark:bg-noc-900 border border-slate-200 dark:border-slate-700/80 hover:border-red-400 dark:hover:border-noc-danger hover:text-red-500 dark:hover:text-noc-danger text-slate-500 dark:text-slate-400 flex items-center justify-center transition-all text-xs shadow-sm">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Container (Full Width Responsive - No max-w-7xl restrictions) -->
    <main class="flex-1 w-full min-h-screen px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 🎯 Bento Grid Metric Bar (Top 4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Bento 1: Estado del Servicio -->
            <div class="noc-card p-5 relative overflow-hidden group">
                <!-- ESTADO DE SERVICIO -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">1. ESTADO DE SERVICIO</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                          :class="routerOnline ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'"
                          x-text="routerOnline ? 'ONLINE' : 'OFFLINE'">
                    </span>
                </div>
                <h2 class="text-xl font-black mt-2 font-mono"
                    :class="routerOnline ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                    x-text="routerOnline ? 'EN LÍNEA' : 'SIN RESPUESTA'">
                </h2>
                <div class="flex items-baseline justify-between mt-1">
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5" x-text="activeRouter?.ip || '0.0.0.0'"></div>
                    <div class="text-right font-mono">
                        <div class="text-xs text-slate-500 dark:text-slate-400">Latencia</div>
                        <div class="text-sm font-bold text-cyan-600 dark:text-noc-cyan" x-text="pingMs !== null ? `${pingMs}ms` : '--'"></div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                    <span>Protocolo: <strong class="text-slate-700 dark:text-slate-300">RouterOS Socket</strong></span>
                    <span>Puerto: <strong class="text-cyan-600 dark:text-noc-cyan" x-text="activeRouter?.puerto_api || 8728">8728</strong></span>
                </div>
            </div>

            <!-- Bento 2: Routers en Red -->
            <div class="noc-card p-5 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">2. Routers en Red</span>
                    <i class="fa-solid fa-network-wired text-cyan-600 dark:text-noc-cyan"></i>
                </div>
                <div class="flex items-baseline justify-between">
                    <div>
                        <div class="text-3xl font-black font-mono text-slate-900 dark:text-white tracking-tight">
                            <span x-text="routersList.length">0</span>
                            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">equipos</span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">Sincronizados MikroHN</div>
                    </div>
                    <div class="text-right font-mono">
                        <div class="text-xs text-slate-500 dark:text-slate-400">Activo</div>
                        <div class="text-xs font-bold text-emerald-600 dark:text-noc-neon truncate max-w-[120px]" x-text="activeRouter?.nombre || 'Default'"></div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                    <span>Base de Datos: <strong class="text-emerald-600 dark:text-noc-neon">mikrohn</strong></span>
                    <span class="text-slate-500 dark:text-slate-400">Modo: <strong class="text-slate-700 dark:text-slate-300">Lectura Directa</strong></span>
                </div>
            </div>

            <!-- Bento 3: Puertos Web / Servicios -->
            <div class="noc-card p-5 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">3. Puertos & Servicios</span>
                    <span class="text-[10px] font-mono text-cyan-600 dark:text-noc-cyan uppercase">Sonda NOC</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                    <!-- HTTP Web 80 -->
                    <div class="flex items-center justify-between p-1.5 rounded bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800/80">
                        <span class="text-slate-700 dark:text-slate-300">HTTP 80</span>
                        <span class="h-2 w-2 rounded-full" :class="servicesCheck?.web?.open ? 'bg-emerald-500 dark:bg-noc-neon shadow-sm' : 'bg-slate-300 dark:bg-slate-600'"></span>
                    </div>
                    <!-- HTTPS SSL 443 -->
                    <div class="flex items-center justify-between p-1.5 rounded bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800/80">
                        <span class="text-slate-700 dark:text-slate-300">SSL 443</span>
                        <span class="h-2 w-2 rounded-full" :class="servicesCheck?.ssl?.open ? 'bg-emerald-500 dark:bg-noc-neon shadow-sm' : 'bg-slate-300 dark:bg-slate-600'"></span>
                    </div>
                    <!-- WinBox 8291 -->
                    <div class="flex items-center justify-between p-1.5 rounded bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800/80">
                        <span class="text-slate-700 dark:text-slate-300">WinBox</span>
                        <span class="h-2 w-2 rounded-full" :class="servicesCheck?.winbox?.open ? 'bg-emerald-500 dark:bg-noc-neon shadow-sm' : 'bg-slate-300 dark:bg-slate-600'"></span>
                    </div>
                    <!-- API 8728 -->
                    <div class="flex items-center justify-between p-1.5 rounded bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800/80">
                        <span class="text-slate-700 dark:text-slate-300">API 8728</span>
                        <span class="h-2 w-2 rounded-full" :class="servicesCheck?.api?.open ? 'bg-emerald-500 dark:bg-noc-neon shadow-sm' : 'bg-slate-300 dark:bg-slate-600'"></span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                    <span>SSH (22): <strong :class="servicesCheck?.ssh?.open ? 'text-emerald-600 dark:text-noc-neon' : 'text-slate-400 dark:text-slate-500'" x-text="servicesCheck?.ssh?.open ? 'Abierto' : 'Inactivo'"></strong></span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500">Auto-check</span>
                </div>
            </div>

            <!-- Bento 4: Uso Global de Recursos (CPU / RAM) -->
            <div class="noc-card p-5 relative overflow-hidden group"
                 :class="cpuLoad > alertThresholdCpu ? 'danger-border-pulse' : ''">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">4. Uso de Recursos</span>
                    <span x-show="cpuLoad > alertThresholdCpu" 
                          class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-red-100 dark:bg-red-950 text-red-600 dark:text-noc-danger border border-red-300 dark:border-noc-danger animate-pulse">
                        ALERTA CPU
                    </span>
                    <span x-show="cpuLoad <= alertThresholdCpu" class="text-xs font-mono text-slate-500 dark:text-slate-400">
                        <span x-text="cpuCount">1</span> Core(s)
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <!-- Circular CPU Gauge -->
                    <div class="relative w-16 h-16 flex items-center justify-center">
                        <svg class="w-16 h-16 transform -rotate-90">
                            <circle cx="32" cy="32" r="26" stroke="currentColor" stroke-width="4" class="text-slate-200 dark:text-slate-800 fill-none" />
                            <circle cx="32" cy="32" r="26" stroke="currentColor" stroke-width="4" 
                                    class="fill-none transition-all duration-500"
                                    :class="cpuLoad > 85 ? 'text-red-500 dark:text-noc-danger' : (cpuLoad > 60 ? 'text-amber-500 dark:text-noc-amber' : 'text-emerald-500 dark:text-noc-neon')"
                                    :stroke-dasharray="163.36"
                                    :stroke-dashoffset="163.36 - (163.36 * (cpuLoad / 100))" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center font-mono">
                            <span class="text-sm font-extrabold text-slate-900 dark:text-white" x-text="`${cpuLoad}%`">0%</span>
                            <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase">CPU</span>
                        </div>
                    </div>

                    <!-- RAM Progress Bar -->
                    <div class="flex-1 font-mono">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-slate-500 dark:text-slate-400">RAM:</span>
                            <span class="font-bold text-slate-800 dark:text-white" x-text="`${ramPercent}%`">0%</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-500"
                                 :class="ramPercent > 85 ? 'bg-red-500 dark:bg-noc-danger' : (ramPercent > 65 ? 'bg-amber-500 dark:bg-noc-amber' : 'bg-cyan-500 dark:bg-noc-cyan')"
                                 :style="`width: ${ramPercent}%`"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                            <span x-text="`${ramUsedMb} MB Usados`">0 MB</span>
                            <span x-text="`${ramTotalMb} MB Total`">0 MB</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                    <span>Frecuencia: <strong class="text-slate-700 dark:text-slate-300" x-text="cpuFrequency">--</strong></span>
                    <span>Uptime: <strong class="text-cyan-600 dark:text-noc-cyan truncate max-w-[100px]" x-text="systemUptime">--</strong></span>
                </div>
            </div>

        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800">
            <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto pb-1" aria-label="Tabs">
                <button @click="currentTab = 'overview'" 
                        :class="currentTab === 'overview' ? 'border-cyan-600 dark:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold bg-cyan-50/70 dark:bg-noc-900/60 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="px-4 py-2.5 rounded-t-xl text-xs sm:text-sm font-mono uppercase tracking-wider border-b-2 flex items-center gap-2 whitespace-nowrap transition-all">
                    <i class="fa-solid fa-gauge-high"></i> Visor Monitoreo en Vivo
                </button>
                <button @click="currentTab = 'traffic'; $nextTick(() => resizeTrafficChart())" 
                        :class="currentTab === 'traffic' ? 'border-cyan-600 dark:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold bg-cyan-50/70 dark:bg-noc-900/60 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="px-4 py-2.5 rounded-t-xl text-xs sm:text-sm font-mono uppercase tracking-wider border-b-2 flex items-center gap-2 whitespace-nowrap transition-all">
                    <i class="fa-solid fa-chart-area"></i> Gráficos de Tráfico (TX / RX)
                </button>
                <button @click="currentTab = 'logs'" 
                        :class="currentTab === 'logs' ? 'border-cyan-600 dark:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold bg-cyan-50/70 dark:bg-noc-900/60 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="px-4 py-2.5 rounded-t-xl text-xs sm:text-sm font-mono uppercase tracking-wider border-b-2 flex items-center gap-2 whitespace-nowrap transition-all">
                    <i class="fa-solid fa-terminal"></i> Registro de Eventos (Log MikroTik)
                </button>
                <button @click="currentTab = 'snmp'" 
                        :class="currentTab === 'snmp' ? 'border-cyan-600 dark:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold bg-cyan-50/70 dark:bg-noc-900/60 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="px-4 py-2.5 rounded-t-xl text-xs sm:text-sm font-mono uppercase tracking-wider border-b-2 flex items-center gap-2 whitespace-nowrap transition-all">
                    <i class="fa-solid fa-microchip"></i> Visor Embebido / SNMP Core
                </button>
                <button @click="currentTab = 'settings'" 
                        :class="currentTab === 'settings' ? 'border-cyan-600 dark:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold bg-cyan-50/70 dark:bg-noc-900/60 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
                        class="px-4 py-2.5 rounded-t-xl text-xs sm:text-sm font-mono uppercase tracking-wider border-b-2 flex items-center gap-2 whitespace-nowrap transition-all">
                    <i class="fa-solid fa-sliders"></i> Ajustes de Sonda
                </button>
            </nav>
        </div>

        <!-- 🎯 TAB 1: VISOR DE MONITOREO EN VIVO (OVERVIEW) -->
        <div x-show="currentTab === 'overview'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Hardware & RouterOS Info Card -->
                <div class="noc-card p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-microchip text-cyan-600 dark:text-noc-cyan text-base"></i>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Hardware RouterBOARD</h3>
                        </div>
                        <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-noc-800 text-cyan-700 dark:text-noc-cyan border border-slate-200 dark:border-noc-cyan/30" x-text="boardModel || 'MikroTik'"></span>
                    </div>

                    <div class="mt-4 space-y-3 font-mono text-xs">
                        <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-500 dark:text-slate-400">Modelo de Placa:</span>
                            <span class="text-slate-800 dark:text-white font-semibold" x-text="boardModel || '--'"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-500 dark:text-slate-400">Arquitectura:</span>
                            <span class="text-slate-800 dark:text-white font-semibold" x-text="architecture || '--'"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-500 dark:text-slate-400">Versión RouterOS:</span>
                            <span class="text-emerald-600 dark:text-noc-neon font-bold" x-text="routerOsVersion || '--'"></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-500 dark:text-slate-400">Frecuencia CPU:</span>
                            <span class="text-slate-800 dark:text-white font-semibold" x-text="cpuFrequency || '--'"></span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-slate-500 dark:text-slate-400">Plataforma:</span>
                            <span class="text-slate-800 dark:text-white font-semibold" x-text="platform || 'MikroTik'"></span>
                        </div>
                    </div>
                </div>

                <!-- Memory & Storage Diagnostics -->
                <div class="noc-card p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-hard-drive text-emerald-600 dark:text-noc-neon text-base"></i>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Memoria & Almacenamiento</h3>
                        </div>
                        <span class="text-xs font-mono text-slate-500 dark:text-slate-400">Diagnóstico NOC</span>
                    </div>

                    <div class="mt-4 space-y-5 font-mono text-xs">
                        <!-- RAM -->
                        <div>
                            <div class="flex justify-between mb-1.5">
                                <span class="text-slate-700 dark:text-slate-300 font-semibold">Memoria RAM</span>
                                <span class="text-slate-900 dark:text-white font-bold" x-text="`${ramPercent}% (${ramUsedMb} MB / ${ramTotalMb} MB)`"></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500" 
                                     :class="ramPercent > 80 ? 'bg-red-500 dark:bg-noc-danger' : 'bg-cyan-500 dark:bg-noc-cyan'"
                                     :style="`width: ${ramPercent}%`"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                <span>Libre: <strong class="text-emerald-600 dark:text-noc-neon" x-text="`${ramFreeMb} MB`"></strong></span>
                                <span>Total: <strong class="text-slate-700 dark:text-slate-300" x-text="`${ramTotalMb} MB`"></strong></span>
                            </div>
                        </div>

                        <!-- HDD / NAND Flash -->
                        <div>
                            <div class="flex justify-between mb-1.5">
                                <span class="text-slate-700 dark:text-slate-300 font-semibold">Disco NAND / Flash</span>
                                <span class="text-slate-900 dark:text-white font-bold" x-text="`${hddPercent}% (${hddUsedMb} MB / ${hddTotalMb} MB)`"></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="hddPercent > 85 ? 'bg-red-500 dark:bg-noc-danger' : 'bg-emerald-500 dark:bg-noc-neon'"
                                     :style="`width: ${hddPercent}%`"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                <span>Espacio Libre: <strong class="text-emerald-600 dark:text-noc-neon" x-text="`${hddFreeMb} MB`"></strong></span>
                                <span>Total HDD: <strong class="text-slate-700 dark:text-slate-300" x-text="`${hddTotalMb} MB`"></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hardware Health (Temp & Voltage) -->
                <div class="noc-card p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-temperature-half text-amber-500 dark:text-noc-amber text-base"></i>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Salud & Sensores</h3>
                        </div>
                        <span class="text-xs font-mono text-slate-500 dark:text-slate-400">/system/health</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <!-- Voltage -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800 text-center font-mono">
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Voltaje</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="healthVoltage || '24.1 V'">-- V</div>
                            <span class="text-[10px] text-emerald-600 dark:text-noc-neon">Estable</span>
                        </div>

                        <!-- Temperature -->
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950/70 border border-slate-200 dark:border-slate-800 text-center font-mono">
                            <span class="text-xs text-slate-500 dark:text-slate-400 uppercase">Temperatura</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" 
                                 :class="healthTemperature && parseInt(healthTemperature) > 65 ? 'text-red-500 dark:text-noc-danger' : 'text-emerald-600 dark:text-noc-neon'" 
                                 x-text="healthTemperature || '36 °C'">-- °C</div>
                            <span class="text-[10px] text-slate-400">Placa Base</span>
                        </div>
                    </div>

                    <!-- Uptime card -->
                    <div class="mt-4 p-3.5 rounded-xl bg-slate-50 dark:bg-noc-900/60 border border-slate-200 dark:border-slate-800/80 font-mono text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-regular fa-clock text-cyan-600 dark:text-noc-cyan"></i>
                            <span class="text-slate-500 dark:text-slate-400">Tiempo Activo (Uptime):</span>
                        </div>
                        <span class="text-slate-900 dark:text-white font-bold" x-text="systemUptime || '--'">--</span>
                    </div>
                </div>

            </div>

            <!-- Interfaces Quick Status Grid -->
            <div class="noc-card p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-ethernet text-cyan-600 dark:text-noc-cyan text-base"></i>
                        <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Interfaces de Red Físicas & Lógicas</h3>
                    </div>
                    <span class="text-xs font-mono text-slate-500 dark:text-slate-400" x-text="`${interfacesList.length} interfaces detectadas`"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
                    <template x-for="iface in interfacesList" :key="iface.id || iface.name">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-noc-950/60 border border-slate-200 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 transition-all font-mono text-xs flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 rounded-full" 
                                      :class="iface.running ? 'bg-emerald-500 dark:bg-noc-neon shadow-sm' : 'bg-slate-300 dark:bg-slate-700'"></span>
                                <div>
                                    <div class="text-slate-900 dark:text-white font-bold" x-text="iface.name"></div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400" x-text="iface.type || 'ether'"></div>
                                </div>
                            </div>
                            <button @click="selectInterfaceForTraffic(iface.name)"
                                    title="Monitorear Tráfico"
                                    class="text-[10px] px-2.5 py-1 rounded-lg bg-white dark:bg-noc-850 hover:bg-cyan-50 dark:hover:bg-noc-800 text-cyan-700 dark:text-noc-cyan border border-slate-200 dark:border-slate-700 hover:border-cyan-400 dark:hover:border-noc-cyan transition-all shadow-sm">
                                Ver TX/RX
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- 🎯 TAB 2: GRÁFICOS DE TRÁFICO EN VIVO (TRAFFIC CHART) -->
        <div x-show="currentTab === 'traffic'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
            
            <div class="noc-card p-6">
                <!-- Chart Controls Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-noc-850 border border-slate-200 dark:border-noc-cyan/40 flex items-center justify-center text-cyan-600 dark:text-noc-cyan">
                            <i class="fa-solid fa-chart-line text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tráfico en Tiempo Real por Interfaz</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Lecturas por socket API (/interface/monitor-traffic once)</p>
                        </div>
                    </div>

                    <!-- Interface Selector -->
                    <div class="flex items-center gap-3">
                        <label class="text-xs font-mono text-slate-500 dark:text-slate-400 uppercase">Interfaz:</label>
                        <select x-model="selectedInterface" 
                                @change="onInterfaceChange()"
                                class="bg-white dark:bg-noc-950 border border-slate-200 dark:border-slate-700 text-cyan-700 dark:text-noc-cyan font-mono text-xs font-bold rounded-xl px-4 py-2 focus:ring-1 focus:ring-cyan-500 dark:focus:ring-noc-cyan focus:outline-none shadow-sm cursor-pointer">
                            <template x-for="iface in interfacesList" :key="iface.name">
                                <option :value="iface.name" x-text="`${iface.name} ${iface.running ? '● (RUNNING)' : ''}`"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Live Meters (Current RX / TX) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 my-5">
                    <!-- RX (Descarga) -->
                    <div class="p-4 rounded-xl bg-cyan-50/50 dark:bg-noc-950/80 border border-cyan-200 dark:border-cyan-500/30 font-mono shadow-sm">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                            <span class="flex items-center gap-1.5 text-cyan-700 dark:text-noc-cyan font-semibold">
                                <i class="fa-solid fa-arrow-down"></i> RX (Descarga)
                            </span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Live Rate</span>
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white" x-text="`${trafficData.rx_mbps} Mbps`">0.00 Mbps</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="`${trafficData.rx_kbps} Kbps • ${trafficData.rx_pps} pps`"></div>
                    </div>

                    <!-- TX (Subida) -->
                    <div class="p-4 rounded-xl bg-emerald-50/50 dark:bg-noc-950/80 border border-emerald-200 dark:border-emerald-500/30 font-mono shadow-sm">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                            <span class="flex items-center gap-1.5 text-emerald-700 dark:text-noc-neon font-semibold">
                                <i class="fa-solid fa-arrow-up"></i> TX (Subida)
                            </span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Live Rate</span>
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white" x-text="`${trafficData.tx_mbps} Mbps`">0.00 Mbps</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="`${trafficData.tx_kbps} Kbps • ${trafficData.tx_pps} pps`"></div>
                    </div>

                    <!-- Peak RX -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950/60 border border-slate-200 dark:border-slate-800 font-mono shadow-sm">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Pico Máximo RX</span>
                        <div class="text-xl font-bold text-cyan-600 dark:text-cyan-400 mt-1" x-text="`${peakRx} Mbps`">0.00 Mbps</div>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Máximo en sesión</span>
                    </div>

                    <!-- Peak TX -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950/60 border border-slate-200 dark:border-slate-800 font-mono shadow-sm">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Pico Máximo TX</span>
                        <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="`${peakTx} Mbps`">0.00 Mbps</div>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Máximo en sesión</span>
                    </div>
                </div>

                <!-- Canvas Chart.js -->
                <div class="relative w-full h-80 sm:h-96 bg-slate-50 dark:bg-noc-950/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-800/80 shadow-inner">
                    <canvas id="trafficChart" class="w-full h-full"></canvas>
                </div>

                <!-- Chart Footer Info -->
                <div class="mt-4 flex flex-wrap items-center justify-between text-xs font-mono text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-cyan-500 inline-block"></span> RX (Download / Entrada)
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> TX (Upload / Salida)
                        </span>
                    </div>
                    <span>Actualización: Cada <strong class="text-cyan-600 dark:text-noc-cyan" x-text="`${pollingIntervalMs / 1000}s`">2s</strong></span>
                </div>
            </div>

        </div>

        <!-- 🎯 TAB 3: REGISTRO DE EVENTOS (EVENTLOG MIKROTIK) -->
        <div x-show="currentTab === 'logs'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
            
            <div class="noc-card p-6">
                <!-- Log Header & Filters -->
                <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-noc-850 border border-slate-200 dark:border-noc-cyan/40 flex items-center justify-center text-cyan-600 dark:text-noc-cyan">
                            <i class="fa-solid fa-terminal text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Registro de Eventos MikroTik (/log/print)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Últimas entradas del búfer de sistema</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <!-- Search Box -->
                        <div class="relative">
                            <input type="text" 
                                   x-model="logSearch"
                                   placeholder="Filtrar mensajes..."
                                   class="bg-white dark:bg-noc-950 border border-slate-200 dark:border-slate-700/80 text-slate-900 dark:text-white font-mono text-xs rounded-xl pl-8 pr-3 py-1.5 focus:outline-none focus:border-cyan-500 dark:focus:border-noc-cyan shadow-sm">
                            <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                        </div>

                        <!-- Refresh Logs Button -->
                        <button @click="fetchLogs()" 
                                class="px-3 py-1.5 rounded-xl bg-white dark:bg-noc-850 border border-slate-200 dark:border-slate-700 hover:border-cyan-500 dark:hover:border-noc-cyan text-xs font-mono text-slate-700 dark:text-slate-200 hover:text-cyan-600 dark:hover:text-noc-cyan transition-all flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-arrows-rotate" :class="loadingLogs ? 'fa-spin' : ''"></i> Actualizar
                        </button>
                    </div>
                </div>

                <!-- Topic Quick Filter Buttons -->
                <div class="flex items-center gap-2 mt-4 overflow-x-auto pb-2 text-xs font-mono">
                    <button @click="logTopicFilter = 'all'" 
                            :class="logTopicFilter === 'all' ? 'bg-cyan-600 text-white dark:bg-noc-cyan dark:text-noc-950 font-bold shadow-sm' : 'bg-slate-100 dark:bg-noc-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1 rounded-lg transition-all">Todos (<span x-text="logsList.length">0</span>)</button>
                    <button @click="logTopicFilter = 'critical'" 
                            :class="logTopicFilter === 'critical' ? 'bg-red-600 dark:bg-noc-danger text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-noc-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1 rounded-lg transition-all">Errores / Críticos</button>
                    <button @click="logTopicFilter = 'warning'" 
                            :class="logTopicFilter === 'warning' ? 'bg-amber-500 dark:bg-noc-amber text-slate-900 dark:text-noc-950 font-bold shadow-sm' : 'bg-slate-100 dark:bg-noc-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1 rounded-lg transition-all">Advertencias</button>
                    <button @click="logTopicFilter = 'auth'" 
                            :class="logTopicFilter === 'auth' ? 'bg-purple-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-noc-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1 rounded-lg transition-all">Accesos / Login</button>
                </div>

                <!-- Log Console Box -->
                <div class="mt-4 bg-slate-50 dark:bg-noc-950 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 font-mono text-xs max-h-[480px] overflow-y-auto space-y-2 select-text">
                    <template x-for="(entry, index) in filteredLogs" :key="index">
                        <div class="p-2.5 rounded-lg border flex items-start gap-3 transition-colors"
                             :class="{
                                'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800/40 text-red-900 dark:text-red-200': entry.level === 'critical',
                                'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/40 text-amber-900 dark:text-amber-200': entry.level === 'warning',
                                'bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800/40 text-purple-900 dark:text-purple-200': entry.level === 'auth',
                                'bg-white dark:bg-noc-900/60 border-slate-200 dark:border-slate-800/60 text-slate-800 dark:text-slate-300': entry.level === 'info'
                             }">
                            <!-- Timestamp -->
                            <span class="text-slate-400 dark:text-slate-500 text-[11px] whitespace-nowrap pt-0.5" x-text="entry.time"></span>
                            
                            <!-- Topic Badge -->
                            <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold whitespace-nowrap"
                                  :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100': entry.level === 'critical',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100': entry.level === 'warning',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100': entry.level === 'auth',
                                    'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300': entry.level === 'info'
                                  }"
                                  x-text="entry.topics"></span>

                            <!-- Message Body -->
                            <span class="flex-1 break-all" x-text="entry.message"></span>
                        </div>
                    </template>

                    <div x-show="filteredLogs.length === 0" class="text-center py-12 text-slate-400 dark:text-slate-500">
                        <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                        <p>No se encontraron registros con los filtros actuales.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- 🎯 TAB 4: VISOR EMBEBIDO / SNMP CORE -->
        <div x-show="currentTab === 'snmp'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
            
            <div class="noc-card p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-noc-850 border border-slate-200 dark:border-noc-cyan/40 flex items-center justify-center text-cyan-600 dark:text-noc-cyan">
                            <i class="fa-solid fa-network-wired text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Métricas Profundas de Interfaces & SNMP</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Tabla detallada de enlaces físicos y lógicos</p>
                        </div>
                    </div>
                </div>

                <!-- Interfaces Detailed Table -->
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left font-mono text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-noc-900/40 text-slate-500 dark:text-slate-400 uppercase text-[11px]">
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4">Interfaz</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">MAC Address</th>
                                <th class="py-3 px-4">MTU</th>
                                <th class="py-3 px-4">Comentario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <template x-for="iface in interfacesList" :key="iface.name">
                                <tr class="hover:bg-slate-50 dark:hover:bg-noc-900/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                              :class="iface.running ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-noc-neon border border-emerald-300 dark:border-noc-neon/40' : 'bg-slate-100 dark:bg-slate-900 text-slate-500 border border-slate-200 dark:border-slate-800'">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="iface.running ? 'bg-emerald-500 dark:bg-noc-neon' : 'bg-slate-400 dark:bg-slate-600'"></span>
                                            <span x-text="iface.running ? 'LINK UP' : 'DOWN'"></span>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white" x-text="iface.name"></td>
                                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300" x-text="iface.type"></td>
                                    <td class="py-3 px-4 text-cyan-600 dark:text-noc-cyan" x-text="iface.mac_address || '--:--:--:--:--:--'"></td>
                                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300" x-text="iface.mtu || '1500'"></td>
                                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400 italic" x-text="iface.comment || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Live Ping Diagnostic Panel -->
                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <h4 class="text-xs font-mono uppercase tracking-wider text-slate-700 dark:text-slate-300 font-bold mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-code text-cyan-600 dark:text-noc-cyan"></i> Diagnóstico de Ping en Vivo
                    </h4>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950 border border-slate-200 dark:border-slate-800 font-mono text-xs flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-slate-500 dark:text-slate-400">Objetivo IP:</span>
                            <span class="text-slate-900 dark:text-white font-bold" x-text="activeRouter?.ip || '0.0.0.0'"></span>
                        </div>
                        <button @click="testLivePing()" 
                                :disabled="testingPing"
                                class="px-4 py-2 rounded-xl bg-white dark:bg-noc-850 hover:bg-slate-100 dark:hover:bg-noc-800 border border-slate-300 dark:border-slate-700 hover:border-cyan-500 dark:hover:border-noc-cyan text-cyan-700 dark:text-noc-cyan font-bold transition-all flex items-center gap-2 text-xs disabled:opacity-50 shadow-sm">
                            <i class="fa-solid fa-bolt" :class="testingPing ? 'fa-spin' : ''"></i>
                            <span>Ejecutar Test de Latencia Socket</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- 🎯 TAB 5: AJUSTES DE SONDA (SETTINGS) -->
        <div x-show="currentTab === 'settings'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
            
            <div class="noc-card p-6 max-w-2xl mx-auto">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-noc-850 border border-slate-200 dark:border-noc-cyan/40 flex items-center justify-center text-cyan-600 dark:text-noc-cyan">
                        <i class="fa-solid fa-sliders text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-mono text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Ajustes de Sonda & Umbrales NOC</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Configuración de telemetría y alarmas en tiempo real</p>
                    </div>
                </div>

                <div class="mt-6 space-y-6 font-mono text-xs">
                    <!-- Polling Frequency -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-slate-700 dark:text-slate-300 font-bold uppercase">Frecuencia de Sondeo (Polling):</label>
                            <span class="text-cyan-700 dark:text-noc-cyan font-extrabold text-sm" x-text="`${pollingIntervalMs / 1000} Segundos`">2 Segundos</span>
                        </div>
                        <input type="range" min="1000" max="10000" step="1000" 
                               x-model="pollingIntervalMs" 
                               @change="restartPollingTimer()"
                               class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-600 dark:accent-noc-cyan">
                        <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                            <span>1s (Ultra Rápido)</span>
                            <span>2s (Recomendado NOC)</span>
                            <span>5s</span>
                            <span>10s (Bajo Consumo)</span>
                        </div>
                    </div>

                    <!-- CPU Alert Threshold -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-slate-700 dark:text-slate-300 font-bold uppercase">Umbral de Alerta CPU Crítica (%):</label>
                            <span class="text-red-600 dark:text-noc-danger font-extrabold text-sm" x-text="`${alertThresholdCpu}%`">85%</span>
                        </div>
                        <input type="range" min="50" max="98" step="5" 
                               x-model="alertThresholdCpu" 
                               class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-red-600 dark:accent-noc-danger">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Dispara una alarma visual con pulso de advertencia y tono de audio si el uso supera este nivel.
                        </p>
                    </div>

                    <!-- Sound Alert Settings -->
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-noc-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">Alertas Sonoras (Web Audio API)</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Generación de frecuencia de audio sintética sin archivos externos</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="testSoundAlert()" 
                                    class="px-3 py-1.5 rounded-lg bg-white dark:bg-noc-850 hover:bg-slate-100 dark:hover:bg-noc-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm">
                                <i class="fa-solid fa-play text-[10px]"></i> Probar
                            </button>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="soundEnabled" @change="toggleSound()" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600 dark:peer-checked:bg-noc-cyan"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer (Full Width Responsive - Cleaned Text) -->
    <footer class="border-t border-slate-200 dark:border-slate-800/80 bg-slate-100/90 dark:bg-noc-900/60 py-4 px-4 sm:px-6 lg:px-8 text-center text-xs font-mono text-slate-500 dark:text-slate-400">
        <div class="w-full flex items-center justify-center">
            <span>SMARTZ MONITOREO NOC &copy; 2026 • Motor Independiente</span>
        </div>
    </footer>

</div>
@endsection

@push('scripts')
<script>
// SweetAlert2 / Toast con temporizador de auto-cierre de 4 segundos
const ToastNOC = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

function nocDashboard() {
    return {
        // Theme Persistence & Toggle
        theme: localStorage.getItem('smartz_theme') || 'light',

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('smartz_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            this.updateChartTheme();
        },

        // Web Audio API Beep Generator
        reproducirAlertaSonido(tipo = 'error') {
            if (!this.soundEnabled) return;
            try {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtxClass) return;
                const audioCtx = new AudioCtxClass();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                
                if (tipo === 'error') {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime); // Tono agudo de alarma
                    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.35);
                } else {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.15);
                }
            } catch (e) {
                console.warn('Audio contextual bloqueado por navegador:', e);
            }
        },

        // 🎯 Sistema de DEDUPLICACIÓN y Centro de Notificaciones
        estadoPrevioRouter: 'ONLINE', // Rastrear el estado previo
        alertasHistorial: [],         // Centro de notificaciones
        notifDropdownOpen: false,     // Menú emergente de campana NOC

        dispararAlertaNoc(titulo, mensaje, tipo = 'error') {
            // 1. Guardar en el centro de notificaciones / campana
            this.alertasHistorial.unshift({
                id: Date.now() + Math.random(),
                titulo: titulo,
                mensaje: mensaje,
                tipo: tipo,
                hora: new Date().toLocaleTimeString('es-HN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
            });
            if (this.alertasHistorial.length > 20) this.alertasHistorial.pop();

            // 2. Reproducir sonido de alarma UNA SOLA VEZ
            this.reproducirAlertaSonido(tipo);

            // 3. Mostrar Toast flotante que se autodestruye en 4 segundos
            ToastNOC.fire({
                icon: tipo,
                title: titulo,
                text: mensaje
            });
        },

        limpiarNotificaciones() {
            this.alertasHistorial = [];
        },

        // Initial Routers Data from Controller
        routersList: @json($routers),
        selectedRouterId: {{ $defaultRouter->id ?? 'null' }},
        activeRouter: null,
        routerOnline: true,
        activeRouterOnline: true,
        estadoAnteriorOnline: true,
        contadorFallos: 0,
        isFetchingMetrics: false,

        // State Tracking for Real-time Incident Alerts
        cpuAlertCooldown: false,
        interfaceStates: {}, // Map { 'ether1': true, ... }
        firstInterfaceCheckDone: false,

        // State Tabs
        currentTab: 'overview',

        // Resource Metrics
        cpuLoad: 0,
        cpuCount: 1,
        cpuFrequency: '--',
        boardModel: '--',
        architecture: '--',
        routerOsVersion: '--',
        platform: 'MikroTik',
        systemUptime: '--',
        
        ramPercent: 0,
        ramUsedMb: 0,
        ramTotalMb: 0,
        ramFreeMb: 0,

        hddPercent: 0,
        hddUsedMb: 0,
        hddTotalMb: 0,
        hddFreeMb: 0,

        healthVoltage: null,
        healthTemperature: null,

        // Traffic Data
        selectedInterface: 'ether1',
        interfacesList: [],
        trafficData: {
            rx_mbps: 0,
            tx_mbps: 0,
            rx_kbps: 0,
            tx_kbps: 0,
            rx_pps: 0,
            tx_pps: 0
        },
        peakRx: 0,
        peakTx: 0,
        chartInstance: null,

        // Services & Ping
        servicesCheck: {},
        pingMs: null,
        testingPing: false,

        // Logs
        logsList: [],
        logSearch: '',
        logTopicFilter: 'all',
        loadingLogs: false,

        // Settings & Timers
        pollingIntervalMs: 2000,
        pollingTimer: null,
        pollingPaused: false,
        alertThresholdCpu: 85,
        soundEnabled: true,
        loading: false,
        localClock: '--:--:--',
        clockTimer: null,

        get filteredLogs() {
            return this.logsList.filter(l => {
                const matchTopic = this.logTopicFilter === 'all' || l.level === this.logTopicFilter;
                const matchSearch = !this.logSearch || 
                                    l.message.toLowerCase().includes(this.logSearch.toLowerCase()) || 
                                    l.topics.toLowerCase().includes(this.logSearch.toLowerCase());
                return matchTopic && matchSearch;
            });
        },

        initDashboard() {
            // Find active router
            this.activeRouter = this.routersList.find(r => r.id == this.selectedRouterId) || this.routersList[0] || null;
            if (this.activeRouter) {
                this.routerOnline = true;
                this.activeRouterOnline = true;
                this.estadoAnteriorOnline = true;
                this.estadoPrevioRouter = 'ONLINE';
                this.contadorFallos = 0;
            }

            // Start Clock
            this.updateClock();
            this.clockTimer = setInterval(() => this.updateClock(), 1000);

            // Init Chart.js
            this.initTrafficChart();

            // Initial Fetch
            if (this.activeRouter) {
                this.fetchInterfaces();
                this.fetchMetrics();
                this.fetchServices();
                this.fetchLogs();
            }

            // Start Polling Timer
            this.restartPollingTimer();
        },

        updateClock() {
            const now = new Date();
            this.localClock = now.toLocaleTimeString('es-HN', { hour12: false });
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            window.soundEnabled = this.soundEnabled;
            window.NocAudio.enabled = this.soundEnabled;
            ToastNOC.fire({
                icon: this.soundEnabled ? 'success' : 'info',
                title: this.soundEnabled ? 'Alertas Sonoras Activadas' : 'Alertas Sonoras Silenciadas',
                text: this.soundEnabled ? 'Se emitirán tonos de advertencia ante anomalías.' : 'El sistema operará en modo silencioso.'
            });
        },

        testSoundAlert() {
            this.reproducirAlertaSonido('error');
            ToastNOC.fire({
                icon: 'warning',
                title: 'Prueba de Tono Alarma',
                text: 'Tono sintetizado generado exitosamente vía Web Audio API.'
            });
        },

        togglePolling() {
            this.pollingPaused = !this.pollingPaused;
            if (this.pollingPaused) {
                if (this.pollingTimer) clearInterval(this.pollingTimer);
                ToastNOC.fire({
                    icon: 'warning',
                    title: 'Sondeo Pausado',
                    text: 'La recolección automática de telemetría está detenida.'
                });
            } else {
                this.restartPollingTimer();
                ToastNOC.fire({
                    icon: 'success',
                    title: 'Sondeo Reanudado',
                    text: `Recolección activa cada ${this.pollingIntervalMs / 1000}s.`
                });
            }
        },

        restartPollingTimer() {
            if (this.pollingTimer) clearInterval(this.pollingTimer);
            if (!this.pollingPaused) {
                this.pollingTimer = setInterval(() => {
                    this.fetchMetrics(false);
                }, this.pollingIntervalMs);
            }
        },

        async changeRouter() {
            this.activeRouter = this.routersList.find(r => r.id == this.selectedRouterId) || null;
            if (!this.activeRouter) return;

            // Sincronizar estado inicial al conmutar router
            this.routerOnline = true;
            this.activeRouterOnline = true;
            this.estadoAnteriorOnline = true;
            this.estadoPrevioRouter = 'ONLINE';
            this.contadorFallos = 0;
            this.firstInterfaceCheckDone = false;
            this.interfaceStates = {};
            this.cpuAlertCooldown = false;

            ToastNOC.fire({
                icon: 'info',
                title: 'Conmutando Router Activo',
                text: `Conectando con ${this.activeRouter.nombre} (${this.activeRouter.ip})...`
            });

            // Reset Peak & Data
            this.peakRx = 0;
            this.peakTx = 0;
            this.resetChartData();

            await this.fetchInterfaces();
            await this.fetchMetrics(true);
            await this.fetchServices();
            await this.fetchLogs();
        },

        async fetchInterfaces() {
            if (!this.activeRouter) return;
            try {
                const res = await fetch(`/api/monitoring/${this.activeRouter.id}/interfaces`);
                const data = await res.json();
                if (data.success && data.interfaces) {
                    this.interfacesList = data.interfaces;
                    
                    // Check for interface link down incident alerts
                    this.checkInterfaceAlerts(this.interfacesList);

                    // Pick running or first interface
                    if (this.interfacesList.length > 0 && !this.selectedInterface) {
                        const running = this.interfacesList.find(i => i.running);
                        this.selectedInterface = running ? running.name : this.interfacesList[0].name;
                    }
                }
            } catch (e) {
                console.error('Error fetching interfaces:', e);
            }
        },

        // Incident Monitor: Detect Physical Interface Link Down
        checkInterfaceAlerts(ifaces) {
            if (!Array.isArray(ifaces)) return;

            ifaces.forEach(iface => {
                const name = iface.name;
                const isRunning = Boolean(iface.running);

                if (this.firstInterfaceCheckDone) {
                    const prevRunning = this.interfaceStates[name];
                    // Si estaba activa (UP) y ahora está caída (DOWN)
                    if (prevRunning === true && isRunning === false) {
                        this.dispararAlertaNoc('Enlace Caído', `🔌 Interfaz ${name} desconectada`, 'error');
                    } else if (prevRunning === false && isRunning === true) {
                        this.dispararAlertaNoc('Enlace Conectado', `🔌 Interfaz ${name} restablecida`, 'success');
                    }
                }
                this.interfaceStates[name] = isRunning;
            });

            this.firstInterfaceCheckDone = true;
        },

        selectInterfaceForTraffic(ifaceName) {
            this.selectedInterface = ifaceName;
            this.currentTab = 'traffic';
            this.$nextTick(() => {
                this.resizeTrafficChart();
                this.onInterfaceChange();
            });
        },

        onInterfaceChange() {
            this.resetChartData();
            ToastNOC.fire({
                icon: 'info',
                title: 'Monitoreo de Interfaz',
                text: `Supervisando flujo en interfaz '${this.selectedInterface}'`
            });
        },

        async fetchMetrics(isManual = false) {
            if (!this.activeRouter || this.isFetchingMetrics) return;
            this.isFetchingMetrics = true;
            if (isManual) this.loading = true;

            try {
                // 1. Obtener Recursos (/resources) - Fuente canónica de estado RouterOS API
                let resRes = null;
                try {
                    const response = await fetch(`/api/monitoring/${this.activeRouter.id}/resources`);
                    if (response.ok) {
                        resRes = await response.json();
                    }
                } catch (e) {
                    console.warn('Error en consulta de recursos:', e);
                }

                // 2. Obtener Tráfico y Ping de manera aislada (su fallo jamás sobreescribe el estado de conexión del router)
                try {
                    const [trafficResp, pingResp] = await Promise.allSettled([
                        fetch(`/api/monitoring/${this.activeRouter.id}/traffic?interface=${encodeURIComponent(this.selectedInterface)}`).then(r => r.ok ? r.json() : null),
                        fetch(`/api/monitoring/${this.activeRouter.id}/ping`).then(r => r.ok ? r.json() : null)
                    ]);

                    if (trafficResp.status === 'fulfilled' && trafficResp.value) {
                        const resTraffic = trafficResp.value;
                        if (resTraffic.success && resTraffic.traffic) {
                            const t = resTraffic.traffic;
                            this.trafficData = t;
                            if (t.rx_mbps > this.peakRx) this.peakRx = t.rx_mbps;
                            if (t.tx_mbps > this.peakTx) this.peakTx = t.tx_mbps;
                            this.appendChartData(t.timestamp || new Date().toLocaleTimeString(), t.rx_mbps, t.tx_mbps);
                        }
                    }

                    if (pingResp.status === 'fulfilled' && pingResp.value) {
                        const resPing = pingResp.value;
                        if (resPing && resPing.ping) {
                            this.pingMs = resPing.ping.online ? resPing.ping.latency_ms : null;
                        }
                    }
                } catch (auxErr) {
                    console.warn('Error en telemetría secundaria (tráfico/ping):', auxErr);
                }

                // 3. Evaluación Estricta del Estado del Router (basada en API RouterOS)
                if (resRes && resRes.success) {
                    const data = resRes;
                    // Asignación explícita de bandera según respuesta API
                    this.routerOnline = Boolean(data.online);
                    this.activeRouterOnline = this.routerOnline;
                    data.nombre = data.nombre || data.router_name || this.activeRouter.nombre;
                    data.ip = data.ip || this.activeRouter.ip;

                    if (this.routerOnline) {
                        // Resetear contador de fallos
                        this.contadorFallos = 0;

                        // Si el router está respondiendo métricas, CERRAR inmediatamente cualquier notificación residual
                        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                            Swal.close();
                        }

                        // Si venía de estado caído, registrar restablecimiento de enlace
                        if (this.estadoAnteriorOnline === false) {
                            this.estadoAnteriorOnline = true;
                            this.estadoPrevioRouter = 'ONLINE';
                            this.dispararAlertaNoc('Enlace Restablecido', `Router ${data.nombre} volvió a estar ONLINE.`, 'success');
                        }

                        const r = data.resources || {};
                        this.cpuLoad = r.cpu_load || 0;
                        this.cpuCount = r.cpu_count || 1;
                        this.cpuFrequency = r.cpu_frequency || '--';
                        this.boardModel = r.board_name || this.activeRouter.modelo || 'RouterBOARD';
                        this.architecture = r.architecture || '--';
                        this.routerOsVersion = r.version || '--';
                        this.platform = r.platform || 'MikroTik';
                        this.systemUptime = r.uptime || '--';

                        if (r.memory) {
                            this.ramPercent = r.memory.percent;
                            this.ramUsedMb = r.memory.used_mb;
                            this.ramTotalMb = r.memory.total_mb;
                            this.ramFreeMb = r.memory.free_mb;
                        }

                        if (r.hdd) {
                            this.hddPercent = r.hdd.percent;
                            this.hddUsedMb = r.hdd.used_mb;
                            this.hddTotalMb = r.hdd.total_mb;
                            this.hddFreeMb = r.hdd.free_mb;
                        }

                        // Incidente: Carga de CPU elevada
                        if (this.cpuLoad > this.alertThresholdCpu) {
                            if (!this.cpuAlertCooldown) {
                                this.cpuAlertCooldown = true;
                                this.dispararAlertaNoc('Alerta de Carga', `🔥 Router ${data.nombre} CPU al ${this.cpuLoad}%`, 'warning');
                                setTimeout(() => {
                                    this.cpuAlertCooldown = false;
                                }, 15000);
                            }
                        } else if (this.cpuLoad <= 80) {
                            this.cpuAlertCooldown = false;
                        }

                        // Health
                        if (data.health && data.health.supported) {
                            this.healthVoltage = data.health.voltage || null;
                            this.healthTemperature = data.health.temperature || data.health.cpu_temperature || null;
                        }
                    } else {
                        // Router respondió pero con online = false
                        this.contadorFallos++;
                        // Solo disparar alarma acústica y Toast si this.routerOnline === false Y el estado anterior era true (transición real de caída)
                        if (this.estadoAnteriorOnline === true) {
                            this.estadoAnteriorOnline = false;
                            this.estadoPrevioRouter = 'OFFLINE';
                            this.dispararAlertaNoc('Alarma NOC', `Router ${data.nombre} (${data.ip}) no responde al sondeo.`, 'error');
                        }
                    }
                } else {
                    // Fallo al obtener respuesta JSON o respuesta inválida
                    this.contadorFallos++;
                    if (this.contadorFallos >= 2) {
                        this.routerOnline = false;
                        this.activeRouterOnline = false;
                        const rName = this.activeRouter?.nombre || 'Router';
                        const rIp = this.activeRouter?.ip || '';

                        // Solo disparar la alarma acústica y el Toast si this.routerOnline === false Y el estado anterior era true (transición de caída real)
                        if (this.estadoAnteriorOnline === true) {
                            this.estadoAnteriorOnline = false;
                            this.estadoPrevioRouter = 'OFFLINE';
                            this.dispararAlertaNoc('Alarma NOC', `Router ${rName} (${rIp}) no responde al sondeo.`, 'error');
                        }
                    }
                }

                // Refresco periódico de interfaces para detección de desconexión física
                if (Math.random() < 0.35) {
                    this.fetchInterfaces();
                }

            } catch (err) {
                console.warn('Error general en ciclo de métricas:', err);
                this.contadorFallos++;
                if (this.contadorFallos >= 2) {
                    this.routerOnline = false;
                    this.activeRouterOnline = false;
                    const rName = this.activeRouter?.nombre || 'Router';
                    const rIp = this.activeRouter?.ip || '';

                    if (this.estadoAnteriorOnline === true) {
                        this.estadoAnteriorOnline = false;
                        this.estadoPrevioRouter = 'OFFLINE';
                        this.dispararAlertaNoc('Alarma NOC', `Router ${rName} (${rIp}) no responde al sondeo.`, 'error');
                    }
                }
            } finally {
                this.isFetchingMetrics = false;
                if (isManual) this.loading = false;
            }
        },

        async fetchServices() {
            if (!this.activeRouter) return;
            try {
                const res = await fetch(`/api/monitoring/${this.activeRouter.id}/services`);
                const data = await res.json();
                if (data.success && data.services) {
                    this.servicesCheck = data.services;
                }
            } catch (e) {
                console.error('Error fetching services:', e);
            }
        },

        async fetchLogs() {
            if (!this.activeRouter) return;
            this.loadingLogs = true;
            try {
                const res = await fetch(`/api/monitoring/${this.activeRouter.id}/logs`);
                const data = await res.json();
                if (data.success && data.logs) {
                    this.logsList = data.logs;
                }
            } catch (e) {
                console.error('Error fetching logs:', e);
            } finally {
                this.loadingLogs = false;
            }
        },

        async testLivePing() {
            if (!this.activeRouter || this.testingPing) return;
            this.testingPing = true;
            try {
                const res = await fetch(`/api/monitoring/${this.activeRouter.id}/ping`);
                const data = await res.json();
                if (data.success && data.ping) {
                    const p = data.ping;
                    if (p.online) {
                        this.reproducirAlertaSonido('info');
                        ToastNOC.fire({
                            icon: 'success',
                            title: 'Ping Exitoso',
                            text: `Respuesta de ${this.activeRouter.ip} en ${p.latency_ms} ms.`
                        });
                    } else {
                        this.reproducirAlertaSonido('error');
                        ToastNOC.fire({
                            icon: 'error',
                            title: 'Ping Fallido',
                            text: p.error || 'Host inalcanzable.'
                        });
                    }
                }
            } catch (e) {
                this.reproducirAlertaSonido('error');
                ToastNOC.fire({
                    icon: 'error',
                    title: 'Error de Red',
                    text: 'No fue posible completar la prueba de ping.'
                });
            } finally {
                this.testingPing = false;
            }
        },

        /* =========================================================================
         *  Chart.js Traffic Implementation (Responsive & Dual Theme)
         * ========================================================================= */
        initTrafficChart() {
            const ctx = document.getElementById('trafficChart');
            if (!ctx) return;

            const isDark = document.documentElement.classList.contains('dark');
            const maxPoints = 30;
            const initialLabels = Array(maxPoints).fill('');
            const initialRx = Array(maxPoints).fill(0);
            const initialTx = Array(maxPoints).fill(0);

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: initialLabels,
                    datasets: [
                        {
                            label: 'RX (Download Mbps)',
                            data: initialRx,
                            borderColor: '#0284c7',
                            backgroundColor: isDark ? 'rgba(0, 240, 255, 0.15)' : 'rgba(2, 132, 199, 0.12)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#0284c7'
                        },
                        {
                            label: 'TX (Upload Mbps)',
                            data: initialTx,
                            borderColor: '#10b981',
                            backgroundColor: isDark ? 'rgba(0, 255, 157, 0.12)' : 'rgba(16, 185, 129, 0.12)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#10b981'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: isDark ? '#94a3b8' : '#475569',
                                font: {
                                    family: 'JetBrains Mono',
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#0b111e' : '#ffffff',
                            titleColor: isDark ? '#f8fafc' : '#0f172a',
                            bodyColor: isDark ? '#e2e8f0' : '#334155',
                            borderColor: isDark ? 'rgba(255,255,255,0.1)' : '#cbd5e1',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            bodyFont: {
                                family: 'JetBrains Mono'
                            },
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(2)} Mbps`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: isDark ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: isDark ? '#64748b' : '#64748b',
                                font: {
                                    family: 'JetBrains Mono',
                                    size: 10
                                },
                                maxRotation: 0
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: isDark ? '#64748b' : '#64748b',
                                font: {
                                    family: 'JetBrains Mono',
                                    size: 10
                                },
                                callback: function(value) {
                                    return value + ' Mbps';
                                }
                            }
                        }
                    }
                }
            });
        },

        updateChartTheme() {
            if (!this.chartInstance) return;
            const isDark = document.documentElement.classList.contains('dark');
            
            this.chartInstance.options.plugins.legend.labels.color = isDark ? '#94a3b8' : '#475569';
            this.chartInstance.options.plugins.tooltip.backgroundColor = isDark ? '#0b111e' : '#ffffff';
            this.chartInstance.options.plugins.tooltip.titleColor = isDark ? '#f8fafc' : '#0f172a';
            this.chartInstance.options.plugins.tooltip.bodyColor = isDark ? '#e2e8f0' : '#334155';
            this.chartInstance.options.plugins.tooltip.borderColor = isDark ? 'rgba(255,255,255,0.1)' : '#cbd5e1';

            this.chartInstance.options.scales.x.grid.color = isDark ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.05)';
            this.chartInstance.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

            this.chartInstance.data.datasets[0].borderColor = isDark ? '#00f0ff' : '#0284c7';
            this.chartInstance.data.datasets[0].backgroundColor = isDark ? 'rgba(0, 240, 255, 0.15)' : 'rgba(2, 132, 199, 0.12)';
            this.chartInstance.data.datasets[1].borderColor = isDark ? '#00ff9d' : '#10b981';
            this.chartInstance.data.datasets[1].backgroundColor = isDark ? 'rgba(0, 255, 157, 0.12)' : 'rgba(16, 185, 129, 0.12)';

            this.chartInstance.update('none');
        },

        appendChartData(label, rx, tx) {
            if (!this.chartInstance) return;

            const labels = this.chartInstance.data.labels;
            const rxData = this.chartInstance.data.datasets[0].data;
            const txData = this.chartInstance.data.datasets[1].data;

            labels.push(label);
            rxData.push(rx);
            txData.push(tx);

            if (labels.length > 30) {
                labels.shift();
                rxData.shift();
                txData.shift();
            }

            this.chartInstance.update('none');
        },

        resetChartData() {
            if (!this.chartInstance) return;
            const maxPoints = 30;
            this.chartInstance.data.labels = Array(maxPoints).fill('');
            this.chartInstance.data.datasets[0].data = Array(maxPoints).fill(0);
            this.chartInstance.data.datasets[1].data = Array(maxPoints).fill(0);
            this.chartInstance.update('none');
        },

        resizeTrafficChart() {
            if (this.chartInstance) {
                this.chartInstance.resize();
            }
        }
    }
}
</script>
@endpush
