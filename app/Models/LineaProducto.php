<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineaProducto extends Model
{
    use SoftDeletes;

    protected $table = 'lineas_producto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'slug',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relación con los productos de esta línea
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'linea_producto_id');
    }

    /**
     * Scope para líneas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
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
     * Obtener la ruta amigable para el modelo (para URLs)
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
