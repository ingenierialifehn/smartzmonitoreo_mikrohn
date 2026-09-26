<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Router;
use App\Services\RouterOsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClienteMonitoreoController extends Controller
{
    protected RouterOsService $routerOs;

    public function __construct(RouterOsService $routerOs)
    {
        $this->routerOs = $routerOs;
    }

    /**
     * Listado de clientes con IP asignada para el router activo
     * GET /api/monitoreo/clientes-activos
     */
    public function clientesActivos(Request $request): JsonResponse
    {
        return $this->getClientesActivos($request);
    }

    /**
     * Consulta exhaustiva de clientes y servicios de internet de MikroHN
     */
    public function getClientesActivos(Request $request): JsonResponse
    {
        $routerId = $request->input('router_id') ?? $request->query('router_id');

        if (!$routerId) {
            $router = Router::where('estado', 'CONECTADO')->first() ?? Router::first();
            $routerId = $router ? $router->id : null;
        }

        $servicios = collect();

        // 1. Intentar consultar desde servicios_internet_cliente (tabla canónica en MikroHN)
        try {
            $query = DB::table('servicios_internet_cliente')
                ->join('clientes', 'servicios_internet_cliente.cliente_id', '=', 'clientes.id')
                ->leftJoin('redes_ipv4', 'servicios_internet_cliente.red_id', '=', 'redes_ipv4.id')
                ->leftJoin('planes_internet', 'servicios_internet_cliente.plan_id', '=', 'planes_internet.id')
                ->whereNotNull('servicios_internet_cliente.ip_address')
                ->where('servicios_internet_cliente.ip_address', '!=', '');

            if ($routerId) {
                $query->where(function ($q) use ($routerId) {
                    $q->where('servicios_internet_cliente.router_id', $routerId)
                      ->orWhere('redes_ipv4.router_id', $routerId)
                      ->orWhere('clientes.router_id', $routerId);
                });
            }

            $servicios = $query->select(
                'clientes.id as cliente_id',
                'servicios_internet_cliente.id as servicio_id',
                DB::raw("COALESCE(clientes.nombre_completo, 'Sin Nombre') as nombre"),
                'servicios_internet_cliente.ip_address as ip',
                'planes_internet.nombre as plan_nombre',
                'planes_internet.descarga_kbps',
                'planes_internet.subida_kbps',
                'servicios_internet_cliente.estado'
            )
            ->orderBy('clientes.id', 'desc')
            ->get();
        } catch (\Throwable $e) {
            // Silencioso para fallback
        }

        // 2. Si falló o vino vacío, intentar tabla servicios_internet
        if ($servicios->isEmpty()) {
            try {
                $query = DB::table('servicios_internet')
                    ->join('clientes', 'servicios_internet.cliente_id', '=', 'clientes.id')
                    ->leftJoin('planes', 'servicios_internet.plan_id', '=', 'planes.id')
                    ->whereNotNull('servicios_internet.ip')
                    ->where('servicios_internet.ip', '!=', '');

                if ($routerId) {
                    $query->where('servicios_internet.router_id', $routerId);
                }

                $servicios = $query->select(
                    'clientes.id as cliente_id',
                    'servicios_internet.id as servicio_id',
                    DB::raw("COALESCE(clientes.nombre_completo, 'Sin Nombre') as nombre"),
                    'servicios_internet.ip',
                    'planes.nombre as plan_nombre',
                    'servicios_internet.estado'
                )
                ->orderBy('clientes.id', 'desc')
                ->get();
            } catch (\Throwable $e) {
                // Silencioso para fallback
            }
        }

        // 3. Fallback a tabla clientes directa
        if ($servicios->isEmpty()) {
            try {
                $query = DB::table('clientes')
                    ->leftJoin('planes_internet', 'clientes.plan_id', '=', 'planes_internet.id');

                if ($routerId) {
                    $query->where('clientes.router_id', $routerId);
                }

                $query->where(function ($q) {
                    $q->whereNotNull('clientes.ip_address')->where('clientes.ip_address', '!=', '');
                });

                $servicios = $query->select(
                    'clientes.id as cliente_id',
                    'clientes.id as servicio_id',
                    DB::raw("COALESCE(clientes.nombre_completo, 'Sin Nombre') as nombre"),
                    'clientes.ip_address as ip',
                    'planes_internet.nombre as plan_nombre',
                    'planes_internet.descarga_kbps',
                    'planes_internet.subida_kbps',
                    'clientes.estado'
                )
                ->orderBy('clientes.id', 'desc')
                ->get();
            } catch (\Throwable $e) {
                // Fallback secundario a clientes con 'planes'
                try {
                    $query = DB::table('clientes')
                        ->leftJoin('planes', 'clientes.plan_id', '=', 'planes.id')
                        ->whereNotNull('clientes.ip')
                        ->where('clientes.ip', '!=', '');

                    if ($routerId) {
                        $query->where('clientes.router_id', $routerId);
                    }

                    $servicios = $query->select(
                        'clientes.id as cliente_id',
                        'clientes.id as servicio_id',
                        DB::raw("COALESCE(clientes.nombre_completo, 'Sin Nombre') as nombre"),
                        'clientes.ip',
                        'planes.nombre as plan_nombre',
                        'clientes.estado'
                    )
                    ->orderBy('clientes.id', 'desc')
                    ->get();
                } catch (\Throwable $e2) {}
            }
        }

        $formateados = $servicios->map(function ($item) {
            $planNombre = $item->plan_nombre ?? 'Plan Estándar';
            if (!empty($item->descarga_kbps) || !empty($item->subida_kbps)) {
                $dw = round(($item->descarga_kbps ?? 0) / 1024, 1);
                $up = round(($item->subida_kbps ?? 0) / 1024, 1);
                $planNombre .= " ({$dw}M/{$up}M)";
            }

            return [
                'id' => $item->servicio_id,
                'cliente_id' => $item->cliente_id,
                'nombre' => trim($item->nombre ?? 'Sin Nombre'),
                'ip' => trim($item->ip ?? ''),
                'plan' => $planNombre,
                'estado' => $item->estado ?? 'ACTIVO'
            ];
        })->values();

        return response()->json([
            'success' => true,
            'clientes' => $formateados,
            'total' => $formateados->count(),
            'router_id' => $routerId ? (int)$routerId : null,
        ]);
    }

    /**
     * Consulta tráfico en vivo de un cliente o servicio específico
     * GET /api/monitoreo/cliente/{id}/trafico
     */
    public function traficoCliente($id): JsonResponse
    {
        $servicio = null;

        try {
            $query = DB::table('servicios_internet_cliente')
                ->join('clientes', 'servicios_internet_cliente.cliente_id', '=', 'clientes.id')
                ->leftJoin('redes_ipv4', 'servicios_internet_cliente.red_id', '=', 'redes_ipv4.id')
                ->leftJoin('planes_internet', 'servicios_internet_cliente.plan_id', '=', 'planes_internet.id');

            $selects = [
                'clientes.id as cliente_id',
                'servicios_internet_cliente.id as servicio_id',
                DB::raw("COALESCE(clientes.nombre_completo, 'Sin Nombre') as nombre"),
                'servicios_internet_cliente.ip_address as ip',
                'planes_internet.nombre as plan_nombre',
                'planes_internet.descarga_kbps',
                'planes_internet.subida_kbps',
                DB::raw("COALESCE(servicios_internet_cliente.router_id, redes_ipv4.router_id, clientes.router_id) as router_id"),
                'servicios_internet_cliente.estado'
            ];

            $servicio = (clone $query)->where('servicios_internet_cliente.id', $id)->select($selects)->first();

            if (!$servicio) {
                $servicio = (clone $query)->where('servicios_internet_cliente.cliente_id', $id)->select($selects)->first();
            }
        } catch (\Throwable $e) {}

        if ($servicio) {
            $ip = trim($servicio->ip ?? '');
            if (empty($ip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no tiene una dirección IP asignada',
                    'download_mbps' => 0.0,
                    'upload_mbps' => 0.0,
                    'download_formateado' => '0 bps',
                    'upload_formateado' => '0 bps',
                    'bytes_rx_formateado' => '0 B',
                    'bytes_tx_formateado' => '0 B',
                ]);
            }

            $router = null;
            if (!empty($servicio->router_id)) {
                $router = Router::find($servicio->router_id);
            }
            if (!$router) {
                $router = Router::where('estado', 'CONECTADO')->first() ?? Router::first();
            }

            if (!$router) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router no configurado o no disponible para este cliente',
                    'download_mbps' => 0.0,
                    'upload_mbps' => 0.0,
                    'download_formateado' => '0 bps',
                    'upload_formateado' => '0 bps',
                    'bytes_rx_formateado' => '0 B',
                    'bytes_tx_formateado' => '0 B',
                ]);
            }

            $clienteObj = (object)[
                'id' => $servicio->servicio_id,
                'cliente_id' => $servicio->cliente_id,
                'nombre_completo' => $servicio->nombre,
                'ip_address' => $ip,
            ];

            $traffic = $this->routerOs->getClientTraffic($router, $clienteObj);

            $planDesc = $servicio->plan_nombre ?: 'Plan Estándar';
            if (!empty($servicio->descarga_kbps) || !empty($servicio->subida_kbps)) {
                $dw = round(($servicio->descarga_kbps ?? 0) / 1024, 1);
                $up = round(($servicio->subida_kbps ?? 0) / 1024, 1);
                $planDesc .= " ({$dw}M/{$up}M)";
            }

            return response()->json(array_merge([
                'cliente_id' => $servicio->cliente_id,
                'servicio_id' => $servicio->servicio_id,
                'nombre' => $servicio->nombre,
                'ip' => $ip,
                'plan' => $planDesc,
            ], $traffic));
        }

        // Fallback clásico a Modelo Cliente
        $cliente = Cliente::with(['router', 'plan'])->find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
                'download_mbps' => 0.0,
                'upload_mbps' => 0.0,
                'download_formateado' => '0 bps',
                'upload_formateado' => '0 bps',
                'bytes_rx_formateado' => '0 B',
                'bytes_tx_formateado' => '0 B',
            ], 404);
        }

        if (empty($cliente->ip_address)) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene una dirección IP asignada',
                'download_mbps' => 0.0,
                'upload_mbps' => 0.0,
                'download_formateado' => '0 bps',
                'upload_formateado' => '0 bps',
                'bytes_rx_formateado' => '0 B',
                'bytes_tx_formateado' => '0 B',
            ]);
        }

        $router = $cliente->router ?? Router::find($cliente->router_id) ?? Router::where('estado', 'CONECTADO')->first() ?? Router::first();

        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router no configurado o no disponible para este cliente',
                'download_mbps' => 0.0,
                'upload_mbps' => 0.0,
                'download_formateado' => '0 bps',
                'upload_formateado' => '0 bps',
                'bytes_rx_formateado' => '0 B',
                'bytes_tx_formateado' => '0 B',
            ]);
        }

        $traffic = $this->routerOs->getClientTraffic($router, $cliente);

        $planDesc = $cliente->plan ? $cliente->plan->nombre : 'Sin plan';
        if ($cliente->plan && ($cliente->plan->descarga_kbps > 0 || $cliente->plan->subida_kbps > 0)) {
            $dw = round($cliente->plan->descarga_kbps / 1024, 1);
            $up = round($cliente->plan->subida_kbps / 1024, 1);
            $planDesc .= " ({$dw}M/{$up}M)";
        }

        return response()->json(array_merge([
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre_completo,
            'ip' => $cliente->ip_address,
            'plan' => $planDesc,
        ], $traffic));
    }
}

