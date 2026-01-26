<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'tipo_venta' => $this->tipo_venta,
            'tipo_comprobante' => $this->tipo_comprobante,
            'numero_comprobante' => $this->numero_comprobante,
            'fecha_venta' => $this->fecha_venta->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'porcentaje_impuesto' => $this->porcentaje_impuesto,
            'impuesto' => $this->impuesto,
            'porcentaje_descuento' => $this->porcentaje_descuento,
            'descuento' => $this->descuento,
            'total' => $this->total,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'detalles' => DetalleVentaResource::collection($this->whenLoaded('detalles')),
            'can_edit' => $this->puede_editarse, // Accessor from model
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
