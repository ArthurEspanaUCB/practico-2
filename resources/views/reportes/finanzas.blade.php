@extends('reportes.layout')

@section('titulo', 'Reporte Financiero')

@section('styles')
    <style>
        .finance-summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .finance-box {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 2px solid #dee2e6;
        }

        .finance-box.ventas {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
        }

        .finance-box.compras {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border-color: #ffc107;
        }

        .finance-box.ganancia {
            background: linear-gradient(135deg, #cce5ff 0%, #b8daff 100%);
            border-color: #007bff;
        }

        .finance-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .finance-label {
            font-size: 11px;
            color: #666;
        }
    </style>
@endsection

@section('content')
    <div class="filters-applied">
        <strong>Período:</strong> {{ $periodo['inicio'] }} - {{ $periodo['fin'] }}
    </div>

    <div class="finance-summary">
        <div class="finance-box ventas">
            <div class="finance-value text-success">Bs. {{ number_format($resumen['total_ventas'], 2) }}</div>
            <div class="finance-label">TOTAL VENTAS ({{ $resumen['cantidad_ventas'] }} operaciones)</div>
        </div>
        <div class="finance-box compras">
            <div class="finance-value text-warning">Bs. {{ number_format($resumen['total_compras'], 2) }}</div>
            <div class="finance-label">TOTAL COMPRAS ({{ $resumen['cantidad_compras'] }} operaciones)</div>
        </div>
        <div class="finance-box ganancia">
            <div class="finance-value {{ $resumen['ganancia_bruta'] >= 0 ? 'text-primary' : 'text-danger' }}">
                Bs. {{ number_format($resumen['ganancia_bruta'], 2) }}
            </div>
            <div class="finance-label">GANANCIA BRUTA</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Resumen del Período</div>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ventas Completadas</td>
                    <td class="text-center">{{ $resumen['cantidad_ventas'] }}</td>
                    <td class="text-right text-success"><strong>Bs.
                            {{ number_format($resumen['total_ventas'], 2) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td>Compras Realizadas</td>
                    <td class="text-center">{{ $resumen['cantidad_compras'] }}</td>
                    <td class="text-right text-warning"><strong>Bs.
                            {{ number_format($resumen['total_compras'], 2) }}</strong>
                    </td>
                </tr>
                <tr style="background-color: #e8f4fd;">
                    <td><strong>Ganancia Bruta del Período</strong></td>
                    <td class="text-center">-</td>
                    <td class="text-right {{ $resumen['ganancia_bruta'] >= 0 ? 'text-success' : 'text-danger' }}">
                        <strong>Bs. {{ number_format($resumen['ganancia_bruta'], 2) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td>Ticket Promedio por Venta</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Bs. {{ number_format($resumen['ticket_promedio_venta'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (count($detalle['ventas_por_dia']) > 0)
        <div class="section">
            <div class="section-title">Ventas por Día</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-right">Total Ventas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detalle['ventas_por_dia'] as $dia)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }}</td>
                            <td class="text-right">Bs. {{ number_format($dia->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
