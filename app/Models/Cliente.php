<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'cliente_id',
        'identificacion',
        'nombre_completo',
        'direccion_principal',
        'telefono_movil',
        'email',
        'ip_address',
        'mac_address',
        'plan_id',
        'router_id',
        'estado',
        'tipo_servicio',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class, 'router_id');
    }

    public function plan()
    {
        return $this->belongsTo(PlanInternet::class, 'plan_id');
    }
}
