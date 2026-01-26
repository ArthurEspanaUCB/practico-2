<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para generación de reportes y estadísticas
 */
class ReporteService
{
    /**
     * Obtener estadísticas del dashboard
     */
    public function getDashboardStats(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioMesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $finMesAnterior = Carbon::now()->subMonth()->endOfMonth();

        // Ventas del mes (Incluimos Completada y Pendiente para reflejar actividad)
        $ventasMes = Venta::whereIn('estado', ['Completada', 'Pendiente'])
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->sum('total');

        $ventasMesAnterior = Venta::whereIn('estado', ['Completada', 'Pendiente'])
            ->whereBetween('fecha_venta', [$inicioMesAnterior, $finMesAnterior])
            ->sum('total');

        // Compras del mes
        $comprasMes = Compra::whereIn('estado', ['Completada', 'Pendiente'])
            ->whereBetween('fecha_compra', [$inicioMes, $finMes])
            ->sum('total');

        // Contadores
        $totalProductos = Producto::where('estado', true)->count();
        $productosStockBajo = Producto::where('estado', true)
            ->whereRaw('stock <= stock_minimo')
            ->count();
        $totalClientes = Cliente::where('estado', true)->count();
        $totalProveedores = Proveedor::where('estado', true)->count();

        // Ventas por día del mes actual
        $ventasPorDia = Venta::whereIn('estado', ['Completada', 'Pendiente'])
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->selectRaw('DATE(fecha_venta) as fecha, SUM(total) as total')
            ->groupByRaw('DATE(fecha_venta)')
            ->orderBy('fecha')
            ->get();

        // Top 5 productos más vendidos del mes
        $topProductos = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->whereIn('ventas.estado', ['Completada', 'Pendiente'])
            ->whereBetween('ventas.fecha_venta', [$inicioMes, $finMes])
            ->selectRaw('productos.nombre, SUM(detalle_ventas.cantidad) as cantidad, SUM(detalle_ventas.subtotal) as total')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Top 5 clientes del mes
        $topClientes = Venta::with('cliente.persona')
            ->whereIn('estado', ['Completada', 'Pendiente'])
            ->whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->selectRaw('cliente_id, SUM(total) as total')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($venta) {
                return [
                    'nombre' => $venta->cliente->persona->nombres ?? 'N/A',
                    'total' => $venta->total,
                ];
            });

        // Últimas ventas
        $ultimasVentas = Venta::with(['cliente.persona', 'detalles'])
            ->whereIn('estado', ['Completada', 'Pendiente'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($venta) {
                return [
                    'codigo' => $venta->codigo,
                    'cliente' => $venta->cliente->persona->nombres ?? 'N/A',
                    'total' => $venta->total,
                    'fecha' => $venta->fecha_venta->format('d/m/Y'),
                    'items' => $venta->detalles->count(),
                ];
            });

        // Productos con stock bajo
        $productosAlerta = Producto::where('estado', true)
            ->whereRaw('stock <= stock_minimo')
            ->orderBy('stock')
            ->limit(5)
            ->get(['nombre', 'stock', 'stock_minimo']);

        // Variación porcentual de ventas
        $variacionVentas = $ventasMesAnterior > 0
            ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
            : 100;

        return [
            'resumen' => [
                'ventas_mes' => round($ventasMes, 2),
                'ventas_mes_anterior' => round($ventasMesAnterior, 2),
                'variacion_ventas' => $variacionVentas,
                'compras_mes' => round($comprasMes, 2),
                'ganancia_estimada' => round($ventasMes - $comprasMes, 2),
                'total_productos' => $totalProductos,
                'productos_stock_bajo' => $productosStockBajo,
                'total_clientes' => $totalClientes,
                'total_proveedores' => $totalProveedores,
            ],
            'graficos' => [
                'ventas_por_dia' => $ventasPorDia,
                'top_productos' => $topProductos,
                'top_clientes' => $topClientes,
            ],
            'alertas' => [
                'productos_stock_bajo' => $productosAlerta,
            ],
            'actividad' => [
                'ultimas_ventas' => $ultimasVentas,
            ],
        ];
    }

    /**
     * Reporte de ventas
     */
    public function getReporteVentas(array $filtros): array
    {
        $query = Venta::with(['cliente.persona', 'detalles.producto']);

        if (! empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_venta', '>=', $filtros['fecha_inicio']);
        }

        if (! empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_venta', '<=', $filtros['fecha_fin']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        $totales = [
            'subtotal' => $ventas->sum('subtotal'),
            'descuento' => $ventas->sum('descuento'),
            'impuesto' => $ventas->sum('impuesto'),
            'total' => $ventas->sum('total'),
            'cantidad' => $ventas->count(),
        ];

        return [
            'titulo' => 'Reporte de Ventas',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'ventas' => $ventas,
            'totales' => $totales,
        ];
    }

    /**
     * Reporte de compras
     */
    public function getReporteCompras(array $filtros): array
    {
        $query = Compra::with(['proveedor.persona', 'detalles.producto']);

        if (! empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_compra', '>=', $filtros['fecha_inicio']);
        }

        if (! empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_compra', '<=', $filtros['fecha_fin']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        $compras = $query->orderBy('fecha_compra', 'desc')->get();

        $totales = [
            'subtotal' => $compras->sum('subtotal'),
            'descuento' => $compras->sum('descuento'),
            'impuesto' => $compras->sum('impuesto'),
            'total' => $compras->sum('total'),
            'cantidad' => $compras->count(),
        ];

        return [
            'titulo' => 'Reporte de Compras',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'compras' => $compras,
            'totales' => $totales,
        ];
    }

    /**
     * Reporte de inventario
     */
    public function getReporteInventario(array $filtros): array
    {
        $query = Producto::with('categoria');

        if (! empty($filtros['categoria_id'])) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        if (! empty($filtros['stock_bajo']) && $filtros['stock_bajo']) {
            $query->whereRaw('stock <= stock_minimo');
        }

        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $query->where('estado', $filtros['estado'] === 'true' || $filtros['estado'] === '1');
        }

        $productos = $query->orderBy('nombre')->get();

        $resumen = [
            'total_productos' => $productos->count(),
            'valor_inventario' => $productos->sum(fn ($p) => $p->stock * $p->precio_compra),
            'valor_venta' => $productos->sum(fn ($p) => $p->stock * $p->precio_venta),
            'productos_sin_stock' => $productos->where('stock', 0)->count(),
            'productos_stock_bajo' => $productos->filter(fn ($p) => $p->stock <= $p->stock_minimo)->count(),
        ];

        return [
            'titulo' => 'Reporte de Inventario',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'productos' => $productos,
            'resumen' => $resumen,
        ];
    }

    /**
     * Reporte de clientes
     */
    public function getReporteClientes(array $filtros): array
    {
        $query = Cliente::with('persona');

        if (! empty($filtros['tipo'])) {
            $query->whereHas('persona', function ($q) use ($filtros) {
                $q->where('tipo_documento', $filtros['tipo']);
            });
        }

        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $query->where('estado', $filtros['estado'] === 'true' || $filtros['estado'] === '1');
        }

        $clientes = $query->orderBy('created_at', 'desc')->get();

        // Agregar estadísticas de compras por cliente
        foreach ($clientes as $cliente) {
            $cliente->total_compras = Venta::where('cliente_id', $cliente->id)
                ->where('estado', 'Completada')
                ->sum('total');
            $cliente->cantidad_compras = Venta::where('cliente_id', $cliente->id)
                ->where('estado', 'Completada')
                ->count();
        }

        return [
            'titulo' => 'Reporte de Clientes',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'clientes' => $clientes,
            'resumen' => [
                'total_clientes' => $clientes->count(),
                'total_ventas' => $clientes->sum('total_compras'),
            ],
        ];
    }

    /**
     * Reporte de proveedores
     */
    public function getReporteProveedores(array $filtros): array
    {
        $query = Proveedor::with('persona');

        if (! empty($filtros['tipo'])) {
            $query->whereHas('persona', function ($q) use ($filtros) {
                $q->where('tipo_documento', $filtros['tipo']);
            });
        }

        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $query->where('estado', $filtros['estado'] === 'true' || $filtros['estado'] === '1');
        }

        $proveedores = $query->orderBy('created_at', 'desc')->get();

        // Agregar estadísticas de compras por proveedor
        foreach ($proveedores as $proveedor) {
            $proveedor->total_compras = Compra::where('proveedor_id', $proveedor->id)
                ->where('estado', 'Completada')
                ->sum('total');
            $proveedor->cantidad_compras = Compra::where('proveedor_id', $proveedor->id)
                ->where('estado', 'Completada')
                ->count();
        }

        return [
            'titulo' => 'Reporte de Proveedores',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'proveedores' => $proveedores,
            'resumen' => [
                'total_proveedores' => $proveedores->count(),
                'total_compras' => $proveedores->sum('total_compras'),
            ],
        ];
    }

    /**
     * Reporte financiero
     */
    public function getReporteFinanzas(array $filtros): array
    {
        $fechaInicio = ! empty($filtros['fecha_inicio'])
            ? Carbon::parse($filtros['fecha_inicio'])
            : Carbon::now()->startOfMonth();

        $fechaFin = ! empty($filtros['fecha_fin'])
            ? Carbon::parse($filtros['fecha_fin'])
            : Carbon::now()->endOfMonth();

        // Ventas por período
        $ventas = Venta::where('estado', 'Completada')
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->get();

        $totalVentas = $ventas->sum('total');

        // Compras por período
        $compras = Compra::where('estado', 'Completada')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->get();

        $totalCompras = $compras->sum('total');

        // Resumen por día
        $ventasPorDia = Venta::where('estado', 'Completada')
            ->whereBetween('fecha_venta', [$fechaInicio, $fechaFin])
            ->selectRaw('DATE(fecha_venta) as fecha, SUM(total) as total')
            ->groupByRaw('DATE(fecha_venta)')
            ->orderBy('fecha')
            ->get();

        $comprasPorDia = Compra::where('estado', 'Completada')
            ->whereBetween('fecha_compra', [$fechaInicio, $fechaFin])
            ->selectRaw('DATE(fecha_compra) as fecha, SUM(total) as total')
            ->groupByRaw('DATE(fecha_compra)')
            ->orderBy('fecha')
            ->get();

        return [
            'titulo' => 'Reporte Financiero',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i'),
            'periodo' => [
                'inicio' => $fechaInicio->format('d/m/Y'),
                'fin' => $fechaFin->format('d/m/Y'),
            ],
            'resumen' => [
                'total_ventas' => round($totalVentas, 2),
                'total_compras' => round($totalCompras, 2),
                'ganancia_bruta' => round($totalVentas - $totalCompras, 2),
                'cantidad_ventas' => $ventas->count(),
                'cantidad_compras' => $compras->count(),
                'ticket_promedio_venta' => $ventas->count() > 0 ? round($totalVentas / $ventas->count(), 2) : 0,
            ],
            'detalle' => [
                'ventas_por_dia' => $ventasPorDia,
                'compras_por_dia' => $comprasPorDia,
            ],
        ];
    }

    /**
     * Obtener datos para filtros
     */
    public function getFiltrosData(): array
    {
        return [
            'clientes' => Cliente::with('persona')
                ->where('estado', true)
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->persona->nombres ?? 'N/A']),
            'proveedores' => Proveedor::with('persona')
                ->where('estado', true)
                ->get()
                ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->persona->nombres ?? 'N/A']),
            'categorias' => Categoria::where('estado', true)->get(['id', 'nombre']),
            'estados_venta' => ['Pendiente', 'Completada', 'Anulada'],
            'estados_compra' => ['Pendiente', 'Completada', 'Anulada'],
        ];
    }
}
