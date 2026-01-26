<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', 'unique:productos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'gt:precio_compra'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya existe',
            'nombre.required' => 'El nombre es obligatorio',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.exists' => 'La categoría seleccionada no existe',
            'precio_compra.required' => 'El precio de compra es obligatorio',
            'precio_compra.min' => 'El precio de compra debe ser mayor o igual a 0',
            'precio_venta.required' => 'El precio de venta es obligatorio',
            'precio_venta.min' => 'El precio de venta debe ser mayor o igual a 0',
            'precio_venta.gt' => 'El precio de venta debe ser mayor al precio de compra',
            'stock.required' => 'El stock es obligatorio',
            'stock_minimo.required' => 'El stock mínimo es obligatorio',
            'unidad_medida.required' => 'La unidad de medida es obligatoria',
            'imagen.image' => 'El archivo debe ser una imagen',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif',
            'imagen.max' => 'La imagen no debe pesar más de 2MB',
        ];
    }
}
