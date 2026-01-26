@extends('reportes.layout')

@section('titulo', 'Reporte de Proveedores')

@section('content')
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-card-value">{{ $resumen['total_proveedores'] }}</div>
            <div class="summary-card-label">TOTAL PROVEEDORES</div>
        </div>
        <div class="summary-card">
            <div class="summary-card-value text-primary">Bs. {{ number_format($resumen['total_compras'], 2) }}</div>
            <div class="summary-card-label">TOTAL EN COMPRAS</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Listado de Proveedores</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">Código</th>
                    <th>Nombre/Razón Social</th>
                    <th style="width: 90px;">RUC/DNI</th>
                    <th style="width: 100px;">Email</th>
                    <th style="width: 80px;">Teléfono</th>
                    <th style="width: 60px;" class="text-center">Compras</th>
                    <th style="width: 80px;" class="text-right">Total</th>
                    <th style="width: 60px;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $proveedor)
                    <tr>
                        <td><strong>{{ $proveedor->codigo }}</strong></td>
                        <td>{{ $proveedor->persona->nombres ?? 'N/A' }}</td>
                        <td>{{ $proveedor->persona->numero_documento ?? '-' }}</td>
                        <td style="font-size: 9px;">{{ $proveedor->persona->email ?? '-' }}</td>
                        <td>{{ $proveedor->persona->telefono ?? '-' }}</td>
                        <td class="text-center">{{ $proveedor->cantidad_compras }}</td>
                        <td class="text-right"><strong>Bs. {{ number_format($proveedor->total_compras, 2) }}</strong></td>
                        <td class="text-center">
                            @if ($proveedor->estado)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron proveedores</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
