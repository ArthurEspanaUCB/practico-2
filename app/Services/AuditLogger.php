<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Registra una acción en el log con formato detallado.
     * Incluye contexto de release, ambiente y user_id para observabilidad.
     */
    public static function log($tipo, $nivel, $descripcion, $exception = null)
    {
        try {
            // Contexto de observabilidad (requisito CD)
            $release = config('app.version', 'dev');
            $environment = app()->environment();

            // Información del usuario con ID explícito
            $userId = Auth::check() ? Auth::id() : null;
            $userName = Auth::check() ? Auth::user()->name : 'Invitado';
            $userEmail = Auth::check() ? Auth::user()->email : null;

            $ip = Request::ip();
            $url = Request::fullUrl();
            $fecha = now()->format('Y-m-d H:i:s');

            // Construir mensaje multilínea con contexto de release
            $mensaje = "\n[" . $fecha . ']';
            $mensaje .= "\n[RELEASE: " . $release . '] [ENV: ' . $environment . ']';
            $mensaje .= "\nTIPO: " . strtoupper($tipo);
            $mensaje .= "\nNIVEL: " . $nivel;
            $mensaje .= "\nDESCRIPCIÓN: " . $descripcion;
            $mensaje .= "\nUSER_ID: " . ($userId ?? 'N/A');
            $mensaje .= "\nUSUARIO: " . $userName . ($userEmail ? ' (' . $userEmail . ')' : '');
            $mensaje .= "\nIP ORIGEN: " . $ip;
            $mensaje .= "\nUBICACIÓN: " . $url;

            if ($exception) {
                // Si es un error crítico, registrar detalles técnicos
                $mensaje .= "\nERROR MSG: " . $exception->getMessage();
                $mensaje .= "\nARCHIVO: " . $exception->getFile() . ':' . $exception->getLine();
            }

            $mensaje .= "\n--------------------------------------------------";

            // Log estructurado con contexto para sistemas de agregación
            $context = [
                'release' => $release,
                'environment' => $environment,
                'user_id' => $userId,
                'user_name' => $userName,
                'ip' => $ip,
                'url' => $url,
                'tipo' => $tipo,
                'nivel' => $nivel,
            ];

            // Usamos el canal 'daily' para rotación diaria de logs
            Log::channel('daily')->info($mensaje, $context);
        } catch (\Exception $e) {
            // Fallback por si falla el logging mismo
            Log::error('Error crítico al intentar registrar log de auditoría: ' . $e->getMessage());
        }
    }

    // Helpers para acciones comunes
    public static function consulta($descripcion)
    {
        self::log('CONSULTA', 'info', $descripcion);
    }

    public static function insercion($descripcion, $datos = null)
    {
        $desc = $descripcion;
        if ($datos) {
            $desc .= ' | Datos: ' . json_encode($datos);
        }
        self::log('INSERCIÓN', 'success', $desc);
    }

    public static function actualizacion($descripcion, $cambios = null)
    {
        $desc = $descripcion;
        if ($cambios) {
            $desc .= ' | Cambios: ' . json_encode($cambios);
        }
        self::log('ACTUALIZACIÓN', 'info', $desc);
    }

    public static function eliminacion($descripcion, $id = null)
    {
        $desc = $descripcion;
        if ($id) {
            $desc .= ' | ID Eliminado: ' . $id;
        }
        self::log('ELIMINACIÓN', 'warning', $desc);
    }

    public static function error($descripcion, $exception = null)
    {
        self::log('ERROR', 'error', $descripcion, $exception);
    }

    public static function login($descripcion)
    {
        self::log('LOGIN', 'info', $descripcion);
    }
}
