@extends('reportes.layout')

@section('titulo', 'Reporte de Compras')

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
            <div class="summary-card-label">TOTAL COMPRAS</div>
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
            <div class="summary-card-value text-warning">Bs. {{ number_format($totales['total'], 2) }}</div>
            <div class="summary-card-label">TOTAL INVERTIDO</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle de Compras</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Código</th>
                    <th style="width: 70px;">Fecha</th>
                    <th>Proveedor</th>
                    <th style="width: 80px;">Comprobante</th>
                    <th style="width: 60px;" class="text-center">Items</th>
                    <th style="width: 70px;" class="text-right">Total</th>
                    <th style="width: 70px;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                    <tr>
                        <td><strong>{{ $compra->codigo }}</strong></td>
                        <td>{{ $compra->fecha_compra->format('d/m/Y') }}</td>
                        <td>{{ $compra->proveedor->persona->nombres ?? 'N/A' }}</td>
                        <td>{{ $compra->numero_comprobante ?? '-' }}</td>
                        <td class="text-center">{{ $compra->detalles->count() }}</td>
                        <td class="text-right"><strong>Bs. {{ number_format($compra->total, 2) }}</strong></td>
                        <td class="text-center">
                            @if ($compra->estado === 'Completada')
                                <span class="badge badge-success">{{ $compra->estado }}</span>
                            @elseif($compra->estado === 'Pendiente')
                                <span class="badge badge-warning">{{ $compra->estado }}</span>
                            @else
                                <span class="badge badge-danger">{{ $compra->estado }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron compras con los filtros aplicados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-box">
        <div class="totals-row total-final">
            <div class="totals-label">TOTAL INVERTIDO EN COMPRAS:</div>
            <div class="totals-value">Bs. {{ number_format($totales['total'], 2) }}</div>
        </div>
    </div>
@endsection
