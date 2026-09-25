<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    use HasFactory;

    protected $table = 'routers';

    protected $fillable = [
        'nombre',
        'tipo_router',
        'ubicacion',
        'ip',
        'usuario',
        'password',
        'modelo',
        'version',
        'clientes',
        'estado',
        'mantenimiento',
        'protegido',
        'seguridad',
        'registro_trafico',
        'control_velocidad',
        'guardar_ip_visitadas',
        'radius_secret',
        'radius_nas_ip',
        'reglas',
    ];

    protected $casts = [
        'mantenimiento' => 'boolean',
        'protegido' => 'boolean',
        'guardar_ip_visitadas' => 'boolean',
        'reglas' => 'array',
    ];

    protected $appends = [
        'puerto_api',
    ];

    public function getPuertoApiAttribute(): int
    {
        // Si la IP viene en formato ip:puerto
        if (str_contains($this->ip, ':')) {
            $parts = explode(':', $this->ip);
            return (int) end($parts);
        }
        return 8728;
    }

    public function getIpLimpiaAttribute(): string
    {
        if (str_contains($this->ip, ':')) {
            $parts = explode(':', $this->ip);
            return $parts[0];
        }
        return $this->ip;
    }
}
