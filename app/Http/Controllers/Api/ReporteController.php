<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API para generación de Reportes
 */
class ReporteController extends Controller
{
    protected ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = $this->reporteService->getDashboardStats();

            // Log de consulta dashboard (opcional, puede generar mucho ruido si es muy frecuente)
            // AuditLogger::consulta("Acceso a Dashboard de estadísticas");

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de ventas
     */
    public function ventasPdf(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'estado' => $request->estado,
                'cliente_id' => $request->cliente_id,
            ];

            $data = $this->reporteService->getReporteVentas($filtros);

            AuditLogger::consulta('Reporte de Ventas generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.ventas', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('reporte-ventas.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de compras
     */
    public function comprasPdf(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'estado' => $request->estado,
                'proveedor_id' => $request->proveedor_id,
            ];

            $data = $this->reporteService->getReporteCompras($filtros);

            AuditLogger::consulta('Reporte de Compras generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.compras', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('reporte-compras.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de inventario/productos
     */
    public function inventarioPdf(Request $request)
    {
        try {
            $filtros = [
                'categoria_id' => $request->categoria_id,
                'stock_bajo' => $request->stock_bajo === 'true',
                'estado' => $request->estado,
            ];

            $data = $this->reporteService->getReporteInventario($filtros);

            AuditLogger::consulta('Reporte de Inventario generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.inventario', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('reporte-inventario.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de clientes
     */
    public function clientesPdf(Request $request)
    {
        try {
            $filtros = [
                'tipo' => $request->tipo,
                'estado' => $request->estado,
            ];

            $data = $this->reporteService->getReporteClientes($filtros);

            AuditLogger::consulta('Reporte de Clientes generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.clientes', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('reporte-clientes.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de proveedores
     */
    public function proveedoresPdf(Request $request)
    {
        try {
            $filtros = [
                'tipo' => $request->tipo,
                'estado' => $request->estado,
            ];

            $data = $this->reporteService->getReporteProveedores($filtros);

            AuditLogger::consulta('Reporte de Proveedores generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.proveedores', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('reporte-proveedores.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reporte de ganancias/resumen financiero
     */
    public function finanzasPdf(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
            ];

            $data = $this->reporteService->getReporteFinanzas($filtros);

            AuditLogger::consulta('Reporte Financiero generado. Filtros: '.json_encode($filtros));

            $pdf = Pdf::loadView('reportes.finanzas', $data);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->stream('reporte-finanzas.pdf');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener datos para filtros de reportes
     */
    public function getFiltros(): JsonResponse
    {
        try {
            $data = $this->reporteService->getFiltrosData();

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener filtros',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
