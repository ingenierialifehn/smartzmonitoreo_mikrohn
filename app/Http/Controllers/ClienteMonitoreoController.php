<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Router;
use App\Services\RouterOsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $routerId = $request->query('router_id');

        if (!$routerId) {
            $router = Router::where('estado', 'CONECTADO')->first() ?? Router::first();
            $routerId = $router ? $router->id : null;
        }

        if (!$routerId) {
            return response()->json([
                'success' => true,
                'clientes' => [],
                'total' => 0,
            ]);
        }

        $clientes = Cliente::with('plan')
            ->where('router_id', $routerId)
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->where('estado', '!=', 'RETIRADO')
            ->orderBy('nombre_completo', 'asc')
            ->get()
            ->map(function ($c) {
                $planNombre = $c->plan ? $c->plan->nombre : 'Sin plan';
                if ($c->plan && ($c->plan->descarga_kbps > 0 || $c->plan->subida_kbps > 0)) {
                    $dw = round($c->plan->descarga_kbps / 1024, 1);
                    $up = round($c->plan->subida_kbps / 1024, 1);
                    $planNombre .= " ({$dw}M/{$up}M)";
                }

                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre_completo,
                    'ip' => $c->ip_address,
                    'plan' => $planNombre,
                    'estado' => $c->estado,
                    'router_id' => $c->router_id,
                ];
            });

        return response()->json([
            'success' => true,
            'clientes' => $clientes,
            'total' => $clientes->count(),
            'router_id' => (int) $routerId,
        ]);
    }

    /**
     * Consulta tráfico en vivo de un cliente específico
     * GET /api/monitoreo/cliente/{id}/trafico
     */
    public function traficoCliente($id): JsonResponse
    {
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
