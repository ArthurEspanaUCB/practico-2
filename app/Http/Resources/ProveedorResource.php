<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->persona->nombre_completo ?? $this->persona->razon_social ?? null,
            'telefono' => $this->persona->telefono ?? null,
            'email' => $this->persona->email ?? null,
            'dias_credito' => $this->dias_credito,
            'credito_disponible' => $this->credito_disponible,
            'estado' => $this->estado,
        ];
    }
}
