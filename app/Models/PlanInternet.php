<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanInternet extends Model
{
    use HasFactory;

    protected $table = 'planes_internet';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'descarga_kbps',
        'subida_kbps',
    ];
}
