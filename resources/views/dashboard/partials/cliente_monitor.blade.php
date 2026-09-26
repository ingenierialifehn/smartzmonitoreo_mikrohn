<!-- MÓDULO: CONSUMO EN TIEMPO REAL POR CLIENTE -->
<div class="bg-white dark:bg-noc-900/90 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800 shadow-sm transition-all duration-200"
     x-data="monitorClienteComponent()"
     x-init="init()">

    <!-- Header del Módulo con Buscador y Selector -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                </span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider font-mono flex items-center gap-2">
                    Consumo Individual por Cliente en Vivo
                    <span class="text-[10px] font-normal px-2 py-0.5 rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-noc-cyan border border-cyan-300 dark:border-noc-cyan/40">Socket MikroTik</span>
                </h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-1">
                Auditoría instantánea de ancho de banda (Simple Queue / PCQ Mangle) desde MikroHN
            </p>
        </div>

        <!-- Buscador y Selector de Clientes -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full lg:w-auto">
            <!-- Input de Búsqueda Rápida -->
            <div class="relative min-w-[200px] flex-1">
                <input type="text" 
                       x-model="filtroBusqueda" 
                       placeholder="Buscar por nombre, IP o plan..." 
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-noc-950 text-slate-800 dark:text-slate-200 pl-8 pr-7 py-2 focus:ring-1 focus:ring-cyan-500 dark:focus:ring-noc-cyan focus:outline-none font-mono">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                <button x-show="filtroBusqueda" 
                        @click="filtroBusqueda = ''" 
                        class="absolute right-2 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs px-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Selector Desplegable con Botón de Actualizar Integrado -->
            <div class="flex items-center gap-1.5 min-w-[260px] flex-1">
                <select x-model="clienteSeleccionadoId" 
                        @change="cambiarCliente()"
                        class="w-full text-xs rounded-xl border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900 text-gray-800 dark:text-gray-200 p-2.5 focus:ring-cyan-500 font-mono">
                    <option value="">-- Seleccionar Cliente para Monitorear --</option>
                    <template x-for="c in (filtroBusqueda ? clientesFiltrados : listaClientes)" :key="c.id">
                        <option :value="c.id" x-text="`${c.nombre} (${c.ip})`"></option>
                    </template>
                </select>
                
                <button type="button" 
                        @click="cargarClientes()" 
                        class="p-2.5 text-gray-500 hover:text-cyan-600 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 rounded-xl transition flex-shrink-0" 
                        title="Actualizar lista de clientes">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Panel de Métricas Rápidas y Gráfico en Vivo (Visible si hay cliente seleccionado) -->
    <div x-show="clienteSeleccionadoId" x-cloak class="pt-5 space-y-5">
        
        <!-- Tarjetas de Métricas en Vivo -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <!-- 1. Descarga Actual (RX) -->
            <div class="bg-cyan-50/60 dark:bg-cyan-950/20 border border-cyan-200/80 dark:border-cyan-900/50 p-4 rounded-xl relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-cyan-800 dark:text-cyan-300 uppercase tracking-wider font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-down text-cyan-600 dark:text-noc-cyan"></i> Descarga (RX)
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-cyan-200/80 dark:bg-cyan-900/60 text-cyan-900 dark:text-noc-cyan">
                        LIVE
                    </span>
                </div>
                <p class="text-2xl font-black text-cyan-700 dark:text-noc-cyan font-mono mt-1.5 tracking-tight" 
                   x-text="traficoActual.download_formateado || '0 bps'"></p>
                <div class="text-[11px] text-cyan-800/70 dark:text-cyan-300/70 font-mono mt-0.5" 
                     x-text="`${traficoActual.download_mbps || 0} Mbps`"></div>
            </div>

            <!-- 2. Subida Actual (TX) -->
            <div class="bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200/80 dark:border-indigo-900/50 p-4 rounded-xl relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-indigo-800 dark:text-indigo-300 uppercase tracking-wider font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-up text-indigo-600 dark:text-indigo-400"></i> Subida (TX)
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-mono font-bold bg-indigo-200/80 dark:bg-indigo-900/60 text-indigo-900 dark:text-indigo-300">
                        LIVE
                    </span>
                </div>
                <p class="text-2xl font-black text-indigo-700 dark:text-indigo-400 font-mono mt-1.5 tracking-tight" 
                   x-text="traficoActual.upload_formateado || '0 bps'"></p>
                <div class="text-[11px] text-indigo-800/70 dark:text-indigo-300/70 font-mono mt-0.5" 
                     x-text="`${traficoActual.upload_mbps || 0} Mbps`"></div>
            </div>

            <!-- 3. IP Asignada y Estado -->
            <div class="bg-slate-50 dark:bg-noc-950/80 border border-slate-200 dark:border-slate-800 p-4 rounded-xl">
                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">
                    IP Asignada
                </span>
                <p class="text-base font-bold text-slate-900 dark:text-white font-mono mt-1.5" 
                   x-text="clienteActual.ip || '—'"></p>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :class="clienteActual.estado === 'ACTIVO' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span x-text="clienteActual.estado || 'ACTIVO'"></span>
                </div>
            </div>

            <!-- 4. Plan Contratado y Shaper -->
            <div class="bg-slate-50 dark:bg-noc-950/80 border border-slate-200 dark:border-slate-800 p-4 rounded-xl">
                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">
                    Plan Contratado
                </span>
                <p class="text-xs font-bold text-slate-900 dark:text-white font-mono mt-1.5 truncate" 
                   :title="clienteActual.plan"
                   x-text="clienteActual.plan || '—'"></p>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5 truncate" 
                     :title="traficoActual.queue_name ? `Cola MikroTik: ${traficoActual.queue_name}` : 'Simple Queue / Mangle'"
                     x-text="traficoActual.queue_name ? `Cola: ${traficoActual.queue_name}` : 'Shaper Dinámico'"></div>
            </div>
        </div>

        <!-- Panel de Bytes Acumulados -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-noc-950/60 border border-slate-200 dark:border-slate-800/80 flex items-center justify-between font-mono text-xs shadow-inner">
                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-down text-cyan-600 dark:text-noc-cyan text-sm"></i>
                    <span>Total Descargado Acumulado (RX):</span>
                </span>
                <span class="font-extrabold text-cyan-700 dark:text-noc-cyan text-sm" 
                      x-text="traficoActual.bytes_rx_formateado || '0 B'">0 B</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-noc-950/60 border border-slate-200 dark:border-slate-800/80 flex items-center justify-between font-mono text-xs shadow-inner">
                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-indigo-600 dark:text-indigo-400 text-sm"></i>
                    <span>Total Subido Acumulado (TX):</span>
                </span>
                <span class="font-extrabold text-indigo-700 dark:text-indigo-400 text-sm" 
                      x-text="traficoActual.bytes_tx_formateado || '0 B'">0 B</span>
            </div>
        </div>

        <!-- Canvas del Gráfico de Tráfico en Tiempo Real (Chart.js) -->
        <div class="h-72 w-full bg-slate-50/60 dark:bg-noc-950/90 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 relative shadow-inner">
            <canvas id="chartClienteTrafico"></canvas>
        </div>

        <!-- Barra de Estado Inferior -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-mono text-slate-500 dark:text-slate-400 pt-1">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Sondeo en vivo activo (intervalo 1.8s) • Ventana 20 muestras</span>
            </div>
            <div class="flex items-center gap-3">
                <span x-text="`Cliente ID: #${clienteActual.id || '--'}`"></span>
                <span>•</span>
                <span x-text="`Último reporte: ${traficoActual.timestamp || '--:--:--'}`"></span>
            </div>
        </div>

    </div>

    <!-- Estado Inicial Vacío: Ningún Cliente Seleccionado -->
    <div x-show="!clienteSeleccionadoId" class="py-14 sm:py-20 text-center font-mono">
        <div class="w-14 h-14 rounded-2xl bg-cyan-50 dark:bg-noc-850 border border-cyan-200 dark:border-cyan-500/30 flex items-center justify-center mx-auto mb-4 text-cyan-600 dark:text-noc-cyan shadow-sm">
            <i class="fa-solid fa-chart-line text-2xl"></i>
        </div>
        <h4 class="font-bold text-slate-800 dark:text-white text-base">Selecciona un cliente para iniciar el monitoreo en vivo</h4>
        <p class="text-slate-500 dark:text-slate-400 text-xs mt-1.5 max-w-md mx-auto">
            El sistema se comunicará directamente con el socket MikroTik para transmitir el consumo instantáneo de subida y bajada en tiempo real.
        </p>
    </div>

</div>
