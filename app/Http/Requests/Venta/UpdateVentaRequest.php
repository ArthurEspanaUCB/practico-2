<?php

namespace App\Http\Requests\Venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'tipo_venta' => ['required', 'in:Contado,Credito'],
            'tipo_comprobante' => ['required', 'in:Factura,Boleta,Nota,Otro'],
            'numero_comprobante' => ['nullable', 'string', 'max:100'],
            'fecha_venta' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_venta'],
            'porcentaje_impuesto' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.porcentaje_descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio',
            'cliente_id.exists' => 'El cliente seleccionado no existe',
            'tipo_venta.required' => 'El tipo de venta es obligatorio',
            'fecha_venta.required' => 'La fecha de venta es obligatoria',
            'detalles.required' => 'Debe agregar al menos un producto',
            'detalles.min' => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required' => 'El producto es obligatorio',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio',
        ];
    }
}
