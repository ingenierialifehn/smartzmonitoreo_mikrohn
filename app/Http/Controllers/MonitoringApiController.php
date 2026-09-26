<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\RouterOsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringApiController extends Controller
{
    protected RouterOsService $routerOs;

    public function __construct(RouterOsService $routerOs)
    {
        $this->routerOs = $routerOs;
    }

    /**
     * Listado rápido de routers
     */
    public function routers(): JsonResponse
    {
        $routers = Router::select('id', 'nombre', 'ip', 'modelo', 'estado', 'clientes', 'mantenimiento')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'routers' => $routers,
            'total' => $routers->count(),
        ]);
    }

    /**
     * Métricas de Recursos (CPU, Memoria, Disco, Uptime, Salud)
     */
    public function resources(Router $router): JsonResponse
    {
        $resources = $this->routerOs->getSystemResources($router);
        $health = $this->routerOs->getSystemHealth($router);

        $enLinea = false;

        // Si ya se obtuvieron recursos del hardware por la API de MikroTik, está estrictamente en línea
        if (!empty($resources) && (isset($resources['uptime']) || !empty($resources['online']))) {
            $enLinea = true;
        } elseif (!empty($health) && !empty($health['supported'])) {
            $enLinea = true;
        } else {
            // Si no hay datos previos, intentar verificación directa por socket API (8728)
            try {
                $targetHost = $router->ip_host ?? $router->ip_limpia ?? $router->ip;
                $targetPort = $router->puerto_api ?? 8728;
                $socket = @fsockopen($targetHost, $targetPort, $errno, $errstr, 2);
                if ($socket) {
                    fclose($socket);
                    $enLinea = true;
                }
            } catch (\Throwable $e) {
                $enLinea = false;
            }
        }

        $estado = $enLinea ? 'ONLINE' : 'OFFLINE';
        $estadoTexto = $enLinea ? 'EN LÍNEA' : 'SIN RESPUESTA';

        if ($enLinea) {
            if (!is_array($resources)) {
                $resources = [];
            }
            $resources['online'] = true;
        }

        return response()->json([
            'success' => true,
            'online' => $enLinea,
            'estado' => $estado,
            'estado_texto' => $estadoTexto,
            'router_id' => $router->id,
            'router_name' => $router->nombre,
            'nombre' => $router->nombre,
            'ip' => $router->ip,
            'resources' => $resources,
            'health' => $health,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Listado de Interfaces de Red
     */
    public function interfaces(Router $router): JsonResponse
    {
        $interfaces = $this->routerOs->getInterfaces($router);

        return response()->json([
            'success' => true,
            'router_id' => $router->id,
            'interfaces' => $interfaces,
        ]);
    }

    /**
     * Monitor de Tráfico en Tiempo Real de una Interfaz
     */
    public function traffic(Router $router, Request $request): JsonResponse
    {
        $interface = $request->query('interface');

        if (empty($interface)) {
            // Si no se especifica interfaz, buscar la primera interfaz activa
            $ifaces = $this->routerOs->getInterfaces($router);
            $running = collect($ifaces)->firstWhere('running', true);
            $interface = $running ? $running['name'] : ($ifaces[0]['name'] ?? 'ether1');
        }

        $traffic = $this->routerOs->getInterfaceTraffic($router, $interface);

        return response()->json([
            'success' => true,
            'router_id' => $router->id,
            'traffic' => $traffic,
        ]);
    }

    /**
     * Registro de Logs de MikroTik
     */
    public function logs(Router $router): JsonResponse
    {
        $logs = $this->routerOs->getLogs($router, 50);

        return response()->json([
            'success' => true,
            'router_id' => $router->id,
            'logs' => $logs,
            'count' => count($logs),
        ]);
    }

    /**
     * Estado de Servicios y Puertos Web/WinBox/API/SSH
     */
    public function services(Router $router): JsonResponse
    {
        $services = $this->routerOs->checkServices($router->ip_limpia);

        return response()->json([
            'success' => true,
            'router_id' => $router->id,
            'ip' => $router->ip_limpia,
            'services' => $services,
        ]);
    }

    /**
     * Test de Latencia y Conexión
     */
    public function ping(Router $router): JsonResponse
    {
        $ping = $this->routerOs->testPing($router->ip_limpia, $router->puerto_api);

        return response()->json([
            'success' => true,
            'router_id' => $router->id,
            'ping' => $ping,
        ]);
    }
}
