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
        'stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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
     * Verificar si el producto está disponible (basado en stock)
     */
    public function getDisponibleAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Obtener la URL completa de la imagen (CORREGIDO)
     */
    public function getImagenUrlAttribute()
    {
        if ($this->imagen) {
            // Si es una URL completa
            if (filter_var($this->imagen, FILTER_VALIDATE_URL)) {
                return $this->imagen;
            }
            // Si es un archivo local
            if (file_exists(public_path('storage/productos/' . $this->imagen))) {
                return asset('storage/productos/' . $this->imagen);
            }
            // Si está en la carpeta images
            if (file_exists(public_path('images/productos/' . $this->imagen))) {
                return asset('images/productos/' . $this->imagen);
            }
            // Si está directamente en images
            if (file_exists(public_path('images/' . $this->imagen))) {
                return asset('images/' . $this->imagen);
            }
        }

        // Imagen por defecto
        return asset('images/default-product.png');
    }

    /**
     * Determinar el estado del stock
     */
    public function getEstadoStockAttribute()
    {
        if ($this->stock <= 0) return 'agotado';
        if ($this->stock <= 10) return 'bajo';
        return 'disponible';
    }

    /**
     * Obtener el valor total del producto (precio * stock)
     */
    public function getValorTotalAttribute()
    {
        return $this->precio * $this->stock;
    }
}
