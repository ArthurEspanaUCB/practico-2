@extends('reportes.layout')

@section('titulo', 'Reporte de Inventario')

@section('content')
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-value">{{ $resumen['total_productos'] }}</div>
            <div class="summary-card-label">PRODUCTOS</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-primary">Bs. {{ number_format($resumen['valor_inventario'], 2) }}</div>
            <div class="summary-card-label">VALOR COSTO</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-success">Bs. {{ number_format($resumen['valor_venta'], 2) }}</div>
            <div class="summary-card-label">VALOR VENTA</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-danger">{{ $resumen['productos_stock_bajo'] }}</div>
            <div class="summary-card-label">STOCK BAJO</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Productos</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">Código</th>
                    <th>Producto</th>
                    <th style="width: 90px;">Categoría</th>
                    <th style="width: 60px;" class="text-center">Stock</th>
                    <th style="width: 60px;" class="text-center">Mínimo</th>
                    <th style="width: 70px;" class="text-right">P. Compra</th>
                    <th style="width: 70px;" class="text-right">P. Venta</th>
                    <th style="width: 60px;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $producto)
                    <tr>
                        <td><strong>{{ $producto->codigo }}</strong></td>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->categoria->nombre ?? 'N/A' }}</td>
                        <td class="text-center {{ $producto->stock <= $producto->stock_minimo ? 'text-danger' : '' }}">
                            <strong>{{ $producto->stock }}</strong>
                        </td>
                        <td class="text-center">{{ $producto->stock_minimo }}</td>
                        <td class="text-right">Bs. {{ number_format($producto->precio_compra, 2) }}</td>
                        <td class="text-right">Bs. {{ number_format($producto->precio_venta, 2) }}</td>
                        <td class="text-center">
                            @if ($producto->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron productos</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-box">
        <div class="totals-row">
            <div class="totals-label">Total Productos:</div>
            <div class="totals-value">{{ $resumen['total_productos'] }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-label">Productos Sin Stock:</div>
            <div class="totals-value text-danger">{{ $resumen['productos_sin_stock'] }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-label">Productos con Stock Bajo:</div>
            <div class="totals-value text-warning">{{ $resumen['productos_stock_bajo'] }}</div>
        </div>
        <div class="totals-row total-final">
            <div class="totals-label">Valor Total del Inventario (Costo):</div>
            <div class="totals-value">Bs. {{ number_format($resumen['valor_inventario'], 2) }}</div>
        </div>
    </div>
@endsection
