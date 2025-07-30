<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => (float) $this->precio,
            'precio_formateado' => $this->precio_formateado,
            'stock' => $this->stock,
            'imagen' => $this->imagen,
            'imagen_url' => $this->imagen_url,
            'linea_producto_id' => $this->linea_producto_id,
            'linea' => $this->whenLoaded('linea', function () {
                return [
                    'id' => $this->linea->id,
                    'nombre' => $this->linea->nombre,
                    'slug' => $this->linea->slug
                ];
            }),
            'disponible' => $this->disponible,
            'estado_stock' => $this->getEstadoStock(),
            'valor_total' => $this->precio * $this->stock,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Determinar el estado del stock
     */
    private function getEstadoStock()
    {
        if ($this->stock <= 0) {
            return 'agotado';
        } elseif ($this->stock <= 10) {
            return 'bajo';
        } else {
            return 'disponible';
        }
    }
}
