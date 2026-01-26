<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleVentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_nombre' => $this->producto->nombre ?? null,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'porcentaje_descuento' => $this->porcentaje_descuento,
            'descuento' => $this->descuento,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];
    }
}
