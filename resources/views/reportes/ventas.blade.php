@extends('reportes.layout')

@section('titulo', 'Reporte de Ventas')

@section('content')
    @if (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']) || !empty($filtros['estado']))
        <div class="filters-applied">
            <strong>Filtros aplicados:</strong>
            @if (!empty($filtros['fecha_inicio']))
                Desde: {{ \Carbon\Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') }} |
            @endif
            @if (!empty($filtros['fecha_fin']))
                Hasta: {{ \Carbon\Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') }} |
            @endif
            @if (!empty($filtros['estado']))
                Estado: {{ $filtros['estado'] }}
            @endif
        </div>
    @endif

    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-value">{{ $totales['cantidad'] }}</div>
            <div class="summary-card-label">TOTAL VENTAS</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-primary">Bs. {{ number_format($totales['subtotal'], 2) }}</div>
            <div class="summary-card-label">SUBTOTAL</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-danger">Bs. {{ number_format($totales['descuento'], 2) }}</div>
            <div class="summary-card-label">DESCUENTOS</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-success">Bs. {{ number_format($totales['total'], 2) }}</div>
            <div class="summary-card-label">TOTAL NETO</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Ventas</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Código</th>
                    <th style="width: 70px;">Fecha</th>
                    <th>Cliente</th>
                    <th style="width: 60px;" class="text-center">Items</th>
                    <th style="width: 70px;" class="text-right">Subtotal</th>
                    <th style="width: 60px;" class="text-right">Desc.</th>
                    <th style="width: 70px;" class="text-right">Total</th>
                    <th style="width: 70px;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas as $venta)
                    <tr>
                        <td><strong>{{ $venta->codigo }}</strong></td>
                        <td>{{ $venta->fecha_venta->format('d/m/Y') }}</td>
                        <td>{{ $venta->cliente->persona->nombres ?? 'N/A' }}</td>
                        <td class="text-center">{{ $venta->detalles->count() }}</td>
                        <td class="text-right">Bs. {{ number_format($venta->subtotal, 2) }}</td>
                        <td class="text-right text-danger">Bs. {{ number_format($venta->descuento, 2) }}</td>
                        <td class="text-right"><strong>Bs. {{ number_format($venta->total, 2) }}</strong></td>
                        <td class="text-center">
                            @if ($venta->estado === 'Completada')
                                <span class="badge badge-success">{{ $venta->estado }}</span>
                            @elseif($venta->estado === 'Pendiente')
                                <span class="badge badge-warning">{{ $venta->estado }}</span>
                            @else
                                <span class="badge badge-danger">{{ $venta->estado }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron ventas con los filtros aplicados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-box">
        <div class="totals-row">
            <div class="totals-label">Subtotal:</div>
            <div class="totals-value">Bs. {{ number_format($totales['subtotal'], 2) }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-label">Descuentos:</div>
            <div class="totals-value text-danger">-Bs. {{ number_format($totales['descuento'], 2) }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-label">Impuestos:</div>
            <div class="totals-value">Bs. {{ number_format($totales['impuesto'], 2) }}</div>
        </div>
        <div class="totals-row total-final">
            <div class="totals-label">TOTAL GENERAL:</div>
            <div class="totals-value">Bs. {{ number_format($totales['total'], 2) }}</div>
        </div>
    </div>
@endsection
