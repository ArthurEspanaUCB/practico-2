@extends('reportes.layout')

@section('titulo', 'Reporte de Clientes')

@section('content')
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-value">{{ $resumen['total_clientes'] }}</div>
            <div class="summary-card-label">TOTAL CLIENTES</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-success">${{ number_format($resumen['total_ventas'], 2) }}</div>
            <div class="summary-card-label">TOTAL EN VENTAS</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Listado de Clientes</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">Código</th>
                    <th>Nombre/Razón Social</th>
                    <th style="width: 90px;">Documento</th>
                    <th style="width: 100px;">Email</th>
                    <th style="width: 80px;">Teléfono</th>
                    <th style="width: 60px;" class="text-center">Compras</th>
                    <th style="width: 80px;" class="text-right">Total</th>
                    <th style="width: 60px;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr>
                        <td><strong>{{ $cliente->codigo }}</strong></td>
                        <td>{{ $cliente->persona->nombres ?? 'N/A' }}</td>
                        <td>{{ $cliente->persona->numero_documento ?? '-' }}</td>
                        <td style="font-size: 9px;">{{ $cliente->persona->email ?? '-' }}</td>
                        <td>{{ $cliente->persona->telefono ?? '-' }}</td>
                        <td class="text-center">{{ $cliente->cantidad_compras }}</td>
                        <td class="text-right"><strong>${{ number_format($cliente->total_compras, 2) }}</strong></td>
                        <td class="text-center">
                            @if ($cliente->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron clientes</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
