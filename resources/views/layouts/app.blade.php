<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smartz Monitoreo NOC | MikroHN')</title>

    <!-- Dynamic NOC / MikroTik Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Script de Inicialización Inmediata de Tema (Previene FOUC - Modo Light por Defecto) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('smartz_theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Google Fonts: Outfit & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        noc: {
                            950: '#070b12',
                            900: '#0b111e',
                            850: '#101827',
                            800: '#172236',
                            700: '#22324e',
                            600: '#344b70',
                            neon: '#00ff9d',
                            cyan: '#00f0ff',
                            amber: '#ffb703',
                            danger: '#ff0055',
                        }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    boxShadow: {
                        'neon-green': '0 0 20px -3px rgba(0, 255, 157, 0.35)',
                        'neon-cyan': '0 0 20px -3px rgba(0, 240, 255, 0.35)',
                        'neon-red': '0 0 20px -3px rgba(255, 0, 85, 0.4)',
                        'neon-amber': '0 0 20px -3px rgba(255, 183, 3, 0.35)',
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>

    <!-- Chart.js 4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Scrollbar Responsive Style */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .dark ::-webkit-scrollbar-track {
            background: #0b111e;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #1e2c44;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #0284c7;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #00f0ff;
        }

        /* Glassmorphism & Card Styles */
        .noc-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05), 0 2px 6px -2px rgba(0, 0, 0, 0.03);
            transition: all 0.25s ease-in-out;
        }
        .dark .noc-card {
            background: rgba(16, 24, 39, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: none;
        }
        .noc-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        }
        .dark .noc-card:hover {
            border-color: rgba(0, 240, 255, 0.25);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        /* Radial progress bar animation */
        @keyframes pulse-neon {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.97); }
        }
        .pulse-live {
            animation: pulse-neon 2s infinite ease-in-out;
        }

        /* Alert Pulse for CPU > 85% */
        @keyframes danger-pulse {
            0%, 100% { border-color: rgba(255, 0, 85, 0.85); box-shadow: 0 0 20px rgba(255, 0, 85, 0.4); }
            50% { border-color: rgba(255, 0, 85, 0.2); box-shadow: none; }
        }
        .danger-border-pulse {
            animation: danger-pulse 1.2s infinite ease-in-out;
        }

        /* SweetAlert NOC Custom Styles (Dual Mode) */
        .swal2-popup.noc-swal-popup {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12) !important;
            color: #0f172a !important;
            border-radius: 1rem !important;
            font-family: 'Outfit', sans-serif !important;
        }
        .dark .swal2-popup.noc-swal-popup {
            background: #0b111e !important;
            border: 1px solid rgba(0, 240, 255, 0.3) !important;
            box-shadow: 0 0 30px rgba(0, 240, 255, 0.2) !important;
            color: #f1f5f9 !important;
        }
        .swal2-toast.noc-swal-toast {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
            color: #0f172a !important;
            border-radius: 0.75rem !important;
            font-family: 'Outfit', sans-serif !important;
        }
        .dark .swal2-toast.noc-swal-toast {
            background: rgba(11, 17, 30, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6) !important;
            color: #f8fafc !important;
        }
        .swal2-toast.noc-swal-toast-danger {
            border-left: 4px solid #ff0055 !important;
        }
        .swal2-toast.noc-swal-toast-success {
            border-left: 4px solid #10b981 !important;
        }
        .dark .swal2-toast.noc-swal-toast-success {
            border-left: 4px solid #00ff9d !important;
        }
        .swal2-toast.noc-swal-toast-warning {
            border-left: 4px solid #f59e0b !important;
        }
        .dark .swal2-toast.noc-swal-toast-warning {
            border-left: 4px solid #ffb703 !important;
        }
        .swal2-toast.noc-swal-toast-info {
            border-left: 4px solid #0284c7 !important;
        }
        .dark .swal2-toast.noc-swal-toast-info {
            border-left: 4px solid #00f0ff !important;
        }
    </style>

    <!-- Alpine.js (Defer) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-800 dark:bg-noc-950 dark:text-slate-200 min-h-screen font-sans selection:bg-cyan-500 selection:text-white dark:selection:bg-noc-cyan dark:selection:text-noc-950 antialiased overflow-x-hidden transition-colors duration-150">

    <!-- Global Sound Alert System (Web Audio API - No Audio Assets Needed) -->
    <script>
        // Generador de Audio Beep (Web Audio API nativo)
        window.reproducirAlertaSonido = function(tipo = 'error') {
            if (window.soundEnabled === false) return;
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
        };

        window.NocAudio = {
            enabled: true,
            play(type = 'error') {
                if (!this.enabled) return;
                const mappedType = (type === 'critical' || type === 'error') ? 'error' : 'info';
                window.reproducirAlertaSonido(mappedType);
            }
        };

        // SweetAlert2 Toast NOC Singleton con auto-cierre de 4 segundos
        window.ToastNOC = Swal.mixin({
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

        // SweetAlert2 Toasts Helper
        window.nocToast = function({ type = 'info', title = '', message = '', timer = 4000 }) {
            const icons = {
                'success': 'success',
                'error': 'error',
                'warning': 'warning',
                'info': 'info'
            };

            const isDark = document.documentElement.classList.contains('dark');

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: timer,
                timerProgressBar: true,
                customClass: {
                    popup: `noc-swal-toast noc-swal-toast-${type === 'error' ? 'danger' : type}`,
                    title: `text-sm font-semibold ${isDark ? 'text-slate-100' : 'text-slate-800'}`,
                    htmlContainer: `text-xs ${isDark ? 'text-slate-300' : 'text-slate-600'}`
                },
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            Toast.fire({
                icon: icons[type] || 'info',
                title: title,
                text: message
            });
        };

        // Modal de confirmación
        window.nocConfirm = async function({ title, text, confirmButtonText = 'Confirmar', cancelButtonText = 'Cancelar' }) {
            const isDark = document.documentElement.classList.contains('dark');
            const res = await Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff0055',
                cancelButtonColor: isDark ? '#172236' : '#64748b',
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                customClass: {
                    popup: 'noc-swal-popup',
                    title: `text-lg font-bold ${isDark ? 'text-white' : 'text-slate-900'}`,
                    htmlContainer: `text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`,
                    confirmButton: 'px-4 py-2 rounded-lg font-medium shadow-md',
                    cancelButton: 'px-4 py-2 rounded-lg font-medium border border-slate-300 dark:border-slate-700'
                }
            });
            return res.isConfirmed;
        };
    </script>

    <!-- Main Content Slot -->
    <div class="relative z-10 flex flex-col min-h-screen">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
