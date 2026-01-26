<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            'tipo_compra' => $this->tipo_compra,
            'tipo_comprobante' => $this->tipo_comprobante,
            'numero_comprobante' => $this->numero_comprobante,
            'fecha_compra' => $this->fecha_compra->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'porcentaje_impuesto' => $this->porcentaje_impuesto,
            'porcentaje_descuento' => $this->porcentaje_descuento,
            'total' => $this->total,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'detalles' => DetalleCompraResource::collection($this->whenLoaded('detalles')),
            'can_edit' => $this->puede_editarse,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
