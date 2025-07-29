<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'imagen',
        'linea_producto_id'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relación con la línea de producto
     */
    public function linea()
    {
        return $this->belongsTo(LineaProducto::class, 'linea_producto_id');
    }

    /**
     * Scope para productos con stock disponible
     */
    public function scopeEnStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope para búsqueda por nombre o descripción
     */
    public function scopeBuscar($query, $search)
    {
        return $query->where('nombre', 'like', "%{$search}%")
            ->orWhere('descripcion', 'like', "%{$search}%");
    }

    /**
     * Scope para productos de una línea específica
     */
    public function scopeDeLinea($query, $lineaId)
    {
        return $query->where('linea_producto_id', $lineaId);
    }

    /**
     * Formatear el precio para mostrar
     */
    public function getPrecioFormateadoAttribute()
    {
        return '$' . number_format($this->precio, 2);
    }

    /**
     * Verificar si el producto está disponible
     */
    public function getDisponibleAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Obtener la URL completa de la imagen
     */
    public function getImagenUrlAttribute()
    {
        if ($this->imagen) {
            return asset('storage/productos/' . $this->imagen);
        }
        return asset('images/default-product.png');
    }
}
