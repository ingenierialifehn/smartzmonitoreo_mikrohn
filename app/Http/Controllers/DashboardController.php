<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $routers = Router::orderBy('id', 'asc')->get();
        $defaultRouter = $routers->first();
        $totalRouters = $routers->count();
        $onlineEstimate = $routers->where('estado', 'CONECTADO')->count();

        return view('dashboard.index', [
            'routers' => $routers,
            'defaultRouter' => $defaultRouter,
            'totalRouters' => $totalRouters,
            'onlineEstimate' => $onlineEstimate,
            'user' => Auth::user(),
        ]);
    }
}
