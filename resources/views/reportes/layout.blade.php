<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Reporte')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 70%;
            vertical-align: middle;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 10px;
            color: #7f8c8d;
        }

        .report-info {
            display: table-cell;
            width: 30%;
            text-align: right;
            vertical-align: middle;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 5px;
        }

        .report-date {
            font-size: 10px;
            color: #7f8c8d;
        }

        .content {
            padding: 10px 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #2c3e50;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }

        table td {
            padding: 6px 5px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 10px;
        }

        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tr:hover {
            background-color: #eef2f7;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #27ae60;
        }

        .text-danger {
            color: #e74c3c;
        }

        .text-warning {
            color: #f39c12;
        }

        .text-primary {
            color: #3498db;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #27ae60;
            color: white;
        }

        .badge-warning {
            background-color: #f39c12;
            color: white;
        }

        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }

        .totals-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 5px 0;
        }

        .totals-label {
            display: table-cell;
            width: 70%;
            text-align: right;
            padding-right: 10px;
        }

        .totals-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
        }

        .total-final {
            font-size: 14px;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
            padding-top: 5px;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .summary-card:first-child {
            border-radius: 5px 0 0 5px;
        }

        .summary-card:last-child {
            border-radius: 0 5px 5px 0;
        }

        .summary-card-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .summary-card-label {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 30px;
            border-top: 1px solid #dee2e6;
            font-size: 9px;
            color: #7f8c8d;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            width: 50%;
        }

        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }

        .filters-applied {
            background-color: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .filters-applied strong {
            color: #0c5460;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="company-name">Sistema de Ventas</div>
                <div class="company-info">
                    RUC: 12345678901 | Dirección: Av. Principal 123<br>
                    Tel: (01) 234-5678 | Email: info@sistemaventas.com
                </div>
            </div>
            <div class="report-info">
                <div class="report-title">@yield('titulo', 'Reporte')</div>
                <div class="report-date">
                    Generado: {{ $fecha_generacion ?? now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                Sistema de Ventas - {{ date('Y') }}
            </div>
            <div class="footer-right">
                Página <span class="pagenum"></span>
            </div>
        </div>
    </div>
</body>
</html>
