<?php

namespace App\Services;

use App\Models\Router;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Comunicación Nativa con RouterOS API
 * Blindado para Alta Concurrencia y Timeouts Ultra Cortos NOC
 */
class RouterOsService
{
    private $socket = null;
    public bool $connected = false;
    public int $port = 8728;
    public float $timeout = 2.0;
    public float $connectTimeout = 1.5;
    public int $attempts = 1;
    public ?string $errorStr = null;
    public ?int $errorNo = null;

    /**
     * Establece conexión directa con el socket de la API MikroTik
     */
    public function connect(string $ip, string $login, string $password, ?int $port = null): bool
    {
        if ($port) {
            $this->port = $port;
        }

        // Si la IP viene como host:puerto
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            $ip = $parts[0];
            $this->port = (int) $parts[1];
        }

        try {
            $this->disconnect();

            for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
                $this->socket = @fsockopen($ip, $this->port, $this->errorNo, $this->errorStr, $this->connectTimeout);

                if ($this->socket) {
                    $sec = (int) $this->timeout;
                    $usec = (int) (($this->timeout - $sec) * 1000000);
                    @stream_set_timeout($this->socket, $sec, $usec);

                    // Login RouterOS >= v6.43 (plain auth)
                    $this->writeWord('/login');
                    $this->writeWord('=name=' . $login);
                    $this->writeWord('=password=' . $password, true);

                    $response = $this->readResponse(false);

                    if (isset($response[0]) && $response[0] === '!done') {
                        $this->connected = true;
                        return true;
                    }

                    // Fallback para RouterOS antiguo (< 6.43 con challenge MD5)
                    if (isset($response[0]) && $response[0] === '!trap') {
                        if (isset($response[1])) {
                            $matches = [];
                            if (preg_match('/=ret=(.*)/', $response[1], $matches)) {
                                $challenge = pack('H*', $matches[1]);
                                $md5 = md5(chr(0) . $password . $challenge);
                                $this->writeWord('/login');
                                $this->writeWord('=name=' . $login);
                                $this->writeWord('=response=00' . $md5, true);

                                $authResponse = $this->readResponse(false);
                                if (isset($authResponse[0]) && $authResponse[0] === '!done') {
                                    $this->connected = true;
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[RouterOsService] Error conectando a {$ip}: " . $e->getMessage());
            $this->connected = false;
            return false;
        }

        $this->connected = false;
        return false;
    }

    /**
     * Cierra el socket
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Ejecuta un comando en RouterOS API
     */
    public function comm(string $command, array $params = []): array
    {
        if (!$this->socket || !$this->connected) {
            return [];
        }

        try {
            $hasParams = !empty($params);
            $this->writeWord($command, !$hasParams);

            if ($hasParams) {
                $count = count($params);
                $i = 0;
                foreach ($params as $k => $v) {
                    $i++;
                    $isLast = ($i === $count);
                    
                    if (is_int($k)) {
                        // Parámetro posicional como "=once=" o "?running=true"
                        $this->writeWord($v, $isLast);
                    } else {
                        $prefix = (isset($k[0]) && ($k[0] === '=' || $k[0] === '?')) ? '' : '=';
                        $this->writeWord($prefix . $k . '=' . $v, $isLast);
                    }
                }
            }

            return $this->readResponse(true);
        } catch (\Throwable $e) {
            Log::warning("[RouterOsService] Error ejecutando '{$command}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Conecta usando una instancia del modelo Router
     */
    public function connectRouter(Router $router): bool
    {
        return $this->connect(
            $router->ip_limpia,
            $router->usuario,
            $router->password,
            $router->puerto_api
        );
    }

    /**
     * Obtiene recursos del sistema (/system/resource/print)
     */
    public function getSystemResources(Router $router): array
    {
        if (!$this->connectRouter($router)) {
            return ['error' => 'No se pudo conectar al Router MikroTik', 'online' => false];
        }

        $res = $this->comm('/system/resource/print');
        $this->disconnect();

        if (!empty($res)) {
            $data = $res[0] ?? (is_array($res) ? reset($res) : []);
            if (is_array($data) && !empty($data)) {
                $cpuLoad = isset($data['cpu-load']) ? (int) $data['cpu-load'] : 0;
            $freeMem = isset($data['free-memory']) ? (int) $data['free-memory'] : 0;
            $totalMem = isset($data['total-memory']) ? (int) $data['total-memory'] : 1;
            $usedMem = max(0, $totalMem - $freeMem);
            $memPercent = round(($usedMem / $totalMem) * 100, 1);

            $freeHdd = isset($data['free-hdd-space']) ? (int) $data['free-hdd-space'] : 0;
            $totalHdd = isset($data['total-hdd-space']) ? (int) $data['total-hdd-space'] : 1;
            $usedHdd = max(0, $totalHdd - $freeHdd);
            $hddPercent = round(($usedHdd / $totalHdd) * 100, 1);

            return [
                'online' => true,
                'uptime' => $data['uptime'] ?? 'N/A',
                'version' => $data['version'] ?? 'N/A',
                'cpu_load' => $cpuLoad,
                'cpu_count' => $data['cpu-count'] ?? '1',
                'cpu_frequency' => isset($data['cpu-frequency']) ? $data['cpu-frequency'] . ' MHz' : 'N/A',
                'board_name' => $data['board-name'] ?? ($router->modelo ?: 'RouterBOARD'),
                'architecture' => $data['architecture-name'] ?? 'N/A',
                'platform' => $data['platform'] ?? 'MikroTik',
                'memory' => [
                    'free_bytes' => $freeMem,
                    'total_bytes' => $totalMem,
                    'used_bytes' => $usedMem,
                    'free_mb' => round($freeMem / (1024 * 1024), 1),
                    'total_mb' => round($totalMem / (1024 * 1024), 1),
                    'used_mb' => round($usedMem / (1024 * 1024), 1),
                    'percent' => $memPercent,
                ],
                'hdd' => [
                    'free_bytes' => $freeHdd,
                    'total_bytes' => $totalHdd,
                    'used_bytes' => $usedHdd,
                    'free_mb' => round($freeHdd / (1024 * 1024), 1),
                    'total_mb' => round($totalHdd / (1024 * 1024), 1),
                    'used_mb' => round($usedHdd / (1024 * 1024), 1),
                    'percent' => $hddPercent,
                ],
            ];
            }
        }

        return ['online' => false, 'error' => 'Respuesta vacía de /system/resource'];
    }

    /**
     * Obtiene salud del sistema (/system/health/print)
     */
    public function getSystemHealth(Router $router): array
    {
        if (!$this->connectRouter($router)) {
            return ['supported' => false];
        }

        $health = $this->comm('/system/health/print');
        $this->disconnect();

        if (empty($health)) {
            return ['supported' => false];
        }

        $result = ['supported' => true];
        foreach ($health as $item) {
            if (isset($item['name']) && isset($item['value'])) {
                $result[$item['name']] = $item['value'];
            }
        }

        // Si viene en formato plano clásico
        if (isset($health[0])) {
            $h0 = $health[0];
            if (isset($h0['voltage'])) $result['voltage'] = round((float)$h0['voltage'] / 10, 1) . ' V';
            if (isset($h0['temperature'])) $result['temperature'] = $h0['temperature'] . ' °C';
            if (isset($h0['cpu-temperature'])) $result['cpu_temperature'] = $h0['cpu-temperature'] . ' °C';
        }

        return $result;
    }

    /**
     * Obtiene interfaces de red del MikroTik (/interface/print)
     */
    public function getInterfaces(Router $router): array
    {
        if (!$this->connectRouter($router)) {
            return [];
        }

        $interfaces = $this->comm('/interface/print');
        $this->disconnect();

        $list = [];
        foreach ($interfaces as $iface) {
            $list[] = [
                'id' => $iface['.id'] ?? '',
                'name' => $iface['name'] ?? 'unknown',
                'type' => $iface['type'] ?? 'ether',
                'running' => isset($iface['running']) && $iface['running'] === 'true',
                'disabled' => isset($iface['disabled']) && $iface['disabled'] === 'true',
                'comment' => $iface['comment'] ?? '',
                'mac_address' => $iface['mac-address'] ?? '',
                'mtu' => $iface['mtu'] ?? '',
            ];
        }

        return $list;
    }

    /**
     * Monitorea tráfico de una interfaz en tiempo real (/interface/monitor-traffic)
     */
    public function getInterfaceTraffic(Router $router, string $interface): array
    {
        if (!$this->connectRouter($router)) {
            return [
                'online' => false,
                'interface' => $interface,
                'rx_bps' => 0,
                'tx_bps' => 0,
                'rx_mbps' => 0,
                'tx_mbps' => 0,
            ];
        }

        $res = $this->comm('/interface/monitor-traffic', [
            'interface' => $interface,
            '=once=' => ''
        ]);
        $this->disconnect();

        if (!empty($res) && isset($res[0])) {
            $data = $res[0];
            $rxBps = isset($data['rx-bits-per-second']) ? (int) $data['rx-bits-per-second'] : 0;
            $txBps = isset($data['tx-bits-per-second']) ? (int) $data['tx-bits-per-second'] : 0;

            return [
                'online' => true,
                'interface' => $interface,
                'rx_bps' => $rxBps,
                'tx_bps' => $txBps,
                'rx_kbps' => round($rxBps / 1000, 2),
                'tx_kbps' => round($txBps / 1000, 2),
                'rx_mbps' => round($rxBps / 1000000, 2),
                'tx_mbps' => round($txBps / 1000000, 2),
                'rx_pps' => isset($data['rx-packets-per-second']) ? (int) $data['rx-packets-per-second'] : 0,
                'tx_pps' => isset($data['tx-packets-per-second']) ? (int) $data['tx-packets-per-second'] : 0,
                'timestamp' => now()->format('H:i:s'),
            ];
        }

        return [
            'online' => false,
            'interface' => $interface,
            'rx_bps' => 0,
            'tx_bps' => 0,
            'rx_mbps' => 0,
            'tx_mbps' => 0,
        ];
    }

    /**
     * Obtiene los logs recientes del router (/log/print)
     */
    public function getLogs(Router $router, int $limit = 40): array
    {
        if (!$this->connectRouter($router)) {
            return [];
        }

        $logs = $this->comm('/log/print');
        $this->disconnect();

        if (empty($logs)) {
            return [];
        }

        // Obtener los últimos $limit logs
        $recent = array_slice($logs, -$limit);
        $result = [];

        foreach ($recent as $entry) {
            $topics = $entry['topics'] ?? 'system';
            $level = 'info';
            if (str_contains($topics, 'error') || str_contains($topics, 'critical')) {
                $level = 'critical';
            } elseif (str_contains($topics, 'warning')) {
                $level = 'warning';
            } elseif (str_contains($topics, 'login') || str_contains($topics, 'account')) {
                $level = 'auth';
            }

            $result[] = [
                'time' => $entry['time'] ?? '',
                'topics' => $topics,
                'message' => $entry['message'] ?? '',
                'level' => $level,
            ];
        }

        return array_reverse($result);
    }

    /**
     * Chequeo de puertos y servicios NOC (HTTP, HTTPS, Winbox, API, SSH)
     */
    public function checkServices(string $ip): array
    {
        $ports = [
            'web' => ['port' => 80, 'name' => 'HTTP Web'],
            'ssl' => ['port' => 443, 'name' => 'HTTPS SSL'],
            'winbox' => ['port' => 8291, 'name' => 'WinBox'],
            'api' => ['port' => 8728, 'name' => 'API RouterOS'],
            'ssh' => ['port' => 22, 'name' => 'SSH'],
        ];

        $status = [];
        foreach ($ports as $key => $info) {
            $t0 = microtime(true);
            $conn = @fsockopen($ip, $info['port'], $errno, $errstr, 0.4);
            $elapsed = round((microtime(true) - $t0) * 1000, 1);

            if ($conn) {
                @fclose($conn);
                $status[$key] = [
                    'name' => $info['name'],
                    'port' => $info['port'],
                    'open' => true,
                    'latency_ms' => $elapsed,
                ];
            } else {
                $status[$key] = [
                    'name' => $info['name'],
                    'port' => $info['port'],
                    'open' => false,
                    'latency_ms' => null,
                ];
            }
        }

        return $status;
    }

    /**
     * Medición de latencia real con ping/connect
     */
    public function testPing(string $ip, int $port = 8728): array
    {
        $t0 = microtime(true);
        $s = @fsockopen($ip, $port, $errNo, $errStr, 1.2);
        $latency = round((microtime(true) - $t0) * 1000, 1);

        if ($s) {
            @fclose($s);
            return [
                'online' => true,
                'latency_ms' => $latency,
            ];
        }

        return [
            'online' => false,
            'latency_ms' => null,
            'error' => $errStr ?: 'Timeout / Host Inalcanzable',
        ];
    }

    /* =========================================================================
     *  Funciones Auxiliares de Protocolo Socket RouterOS
     * ========================================================================= */

    private function writeWord(string $word, bool $appendNull = false): void
    {
        if (!$this->socket) return;

        @fwrite($this->socket, $this->encodeLength(strlen($word)) . $word);
        if ($appendNull) {
            @fwrite($this->socket, chr(0));
        }
    }

    private function readResponse(bool $parse = true): array
    {
        $response = [];
        if (!$this->socket) return [];

        try {
            while (true) {
                $byteStr = @fread($this->socket, 1);
                $meta = @stream_get_meta_data($this->socket);
                if ($meta && !empty($meta['timed_out'])) {
                    break;
                }

                if (!is_string($byteStr) || strlen($byteStr) === 0) {
                    break;
                }

                $byte = ord($byteStr[0]);
                $length = 0;

                if ($byte & 128) {
                    if (($byte & 192) == 128) {
                        $length = (($byte & 63) << 8) + $this->readByte();
                    } else if (($byte & 224) == 192) {
                        $length = (($byte & 31) << 8) + $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                    } else if (($byte & 240) == 224) {
                        $length = (($byte & 15) << 8) + $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                    } else {
                        $length = $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                        $length = ($length << 8) + $this->readByte();
                    }
                } else {
                    $length = $byte;
                }

                if ($length > 0) {
                    $received = '';
                    $offset = 0;
                    while ($offset < $length) {
                        $chunk = @fread($this->socket, $length - $offset);
                        $meta = @stream_get_meta_data($this->socket);
                        if ($meta && !empty($meta['timed_out'])) {
                            break 2;
                        }
                        if (!is_string($chunk) || strlen($chunk) === 0) {
                            break 2;
                        }
                        $offset += strlen($chunk);
                        $received .= $chunk;
                    }
                    $response[] = $received;
                }

                if ($length == 0) {
                    $last = end($response);
                    if ($last === '!done' || $last === '!trap' || $last === '!fatal' || str_contains((string)$last, '!done') || str_contains((string)$last, '!trap')) {
                        break;
                    }
                    if (empty($response)) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[RouterOsService] Excepción de lectura: " . $e->getMessage());
        }

        if ($parse) {
            return $this->parseResponse($response);
        }

        return $response;
    }

    private function readByte(): int
    {
        if (!$this->socket) return 0;
        $b = @fread($this->socket, 1);
        return (is_string($b) && strlen($b) > 0) ? ord($b[0]) : 0;
    }

    private function parseResponse(array $raw): array
    {
        $result = [];
        $index = 0;

        foreach ($raw as $line) {
            if ($line === '!re') {
                $index++;
            } elseif (str_starts_with($line, '=')) {
                $parts = explode('=', ltrim($line, '='), 2);
                if (count($parts) === 2) {
                    $targetIdx = max(0, $index > 0 ? $index - 1 : 0);
                    $result[$targetIdx][$parts[0]] = $parts[1];
                }
            }
        }

        return array_values($result);
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        if ($length < 0x4000) {
            $length |= 0x8000;
            return chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        if ($length < 0x200000) {
            $length |= 0xC00000;
            return chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        if ($length < 0x10000000) {
            $length |= 0xE0000000;
            return chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
    }
}
