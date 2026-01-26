<?php

namespace App\Http\Requests\Compra;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'tipo_compra' => ['required', 'in:Contado,Credito'],
            'tipo_comprobante' => ['required', 'in:Factura,Nota,Recibo,Otro'],
            'numero_comprobante' => ['nullable', 'string', 'max:100'],
            'fecha_compra' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_compra'],
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
            'proveedor_id.required' => 'El proveedor es obligatorio',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'tipo_compra.required' => 'El tipo de compra es obligatorio',
            'tipo_compra.in' => 'El tipo de compra debe ser Contado o Credito',
            'tipo_comprobante.required' => 'El tipo de comprobante es obligatorio',
            'fecha_compra.required' => 'La fecha de compra es obligatoria',
            'fecha_compra.date' => 'La fecha de compra debe ser una fecha válida',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser posterior a la fecha de compra',
            'detalles.required' => 'Debe agregar al menos un producto',
            'detalles.min' => 'Debe agregar al menos un producto',
            'detalles.*.producto_id.required' => 'El producto es obligatorio',
            'detalles.*.producto_id.exists' => 'El producto seleccionado no existe',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio',
            'detalles.*.precio_unitario.min' => 'El precio unitario debe ser mayor a 0',
        ];
    }
}
