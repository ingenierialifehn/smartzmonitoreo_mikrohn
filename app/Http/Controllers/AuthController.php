<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        try {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo verificar Auth::check() en showLogin: ' . $e->getMessage());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuarioInput = trim($credentials['usuario']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // 1. LOGIN INSTANTÁNEO: Autenticación 100% puramente local en MySQL contra tabla 'users'
        // NINGUNA conexión a MikroTik ni socket API debe ejecutarse durante el ciclo de vida del request HTTP de login.
        try {
            $user = User::select('id', 'name', 'usuario', 'email', 'password', 'status')
                        ->where('usuario', $usuarioInput)
                        ->orWhere('email', $usuarioInput)
                        ->first();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Fallo de conexión MySQL en AuthController@login: ' . $e->getMessage());

            $dbErrorMessage = config('app.debug')
                ? 'Error de conexión MySQL: ' . $e->getMessage()
                : 'No fue posible conectar con la Base de Datos MySQL. Verifique las variables de entorno DB_HOST, DB_DATABASE, DB_USERNAME y DB_PASSWORD en el panel de Render.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $dbErrorMessage
                ], 500);
            }

            throw ValidationException::withMessages([
                'usuario' => $dbErrorMessage,
            ]);
        }

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas. Usuario o correo no registrado.'
                ], 422);
            }
            throw ValidationException::withMessages([
                'usuario' => 'Credenciales inválidas. Usuario o correo no registrado.',
            ]);
        }

        // Verificar estatus activo si aplica
        if (isset($user->status) && $user->status == 0) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuario se encuentra inactivo en el sistema MikroHN.'
                ], 403);
            }
            throw ValidationException::withMessages([
                'usuario' => 'Este usuario se encuentra inactivo en el sistema MikroHN.',
            ]);
        }

        // Verificar contraseña con Bcrypt local
        if (!Hash::check($password, $user->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña incorrecta. Verifique sus datos.'
                ], 422);
            }
            throw ValidationException::withMessages([
                'usuario' => 'Contraseña incorrecta. Verifique sus datos.',
            ]);
        }

        // Autenticar la sesión inmediatamente
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa. Conectando al NOC...',
                'redirect' => route('dashboard')
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente.',
                'redirect' => route('login')
            ]);
        }

        return redirect()->route('login');
    }
}
