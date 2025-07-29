<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear la tabla de líneas de producto
        Schema::create('lineas_producto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->string('slug', 50)->unique();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // creacion de indices para mejorar la busqueda
        Schema::table('lineas_producto', function (Blueprint $table) {
            $table->index('nombre');
            $table->index('activa');
            $table->index(['nombre', 'activa']);
            $table->index(['descripcion', 'activa']);
        });

        // Insertar datos iniciales
        DB::table('lineas_producto')->insert([
            ['nombre' => 'Ropa para caballero', 'slug' => 'ropa-caballero', 'descripcion' => 'Ropa formal e informal para hombre'],
            ['nombre' => 'Ropa para dama', 'slug' => 'ropa-dama', 'descripcion' => 'Ropa formal e informal para mujer'],
            ['nombre' => 'Zapatos', 'slug' => 'zapatos', 'descripcion' => 'Calzado para toda ocasión'],
            ['nombre' => 'Accesorios', 'slug' => 'accesorios', 'descripcion' => 'Complementos de moda'],
        ]);


        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 500)->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('imagen')->nullable();

            // Clave foránea para la línea de producto
            $table->unsignedBigInteger('linea_producto_id');

            $table->timestamps();
            $table->softDeletes();

            // Relación con línea de producto
            $table->foreign('linea_producto_id')
                ->references('id')
                ->on('lineas_producto')
                ->onDelete('restrict');
        });

        // Insertar datos iniciales en productos
        DB::table('productos')->insert([
            [
                'nombre' => 'Camisa Casual',
                'descripcion' => 'Camisa de algodón para uso diario',
                'precio' => 29.99,
                'stock' => 100,
                'imagen' => 'camisa_casual.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Noche',
                'descripcion' => 'Vestido elegante para ocasiones especiales',
                'precio' => 79.99,
                'stock' => 50,
                'imagen' => 'vestido_noche.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Zapatillas Deportivas',
                'descripcion' => 'Zapatillas cómodas para hacer ejercicio',
                'precio' => 59.99,
                'stock' => 200,
                'imagen' => 'zapatillas_deportivas.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bufanda de Lana',
                'descripcion' => 'Bufanda suave y cálida para el invierno',
                'precio' => 19.99,
                'stock' => 150,
                'imagen' => 'bufanda_lana.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Pantalones Jeans',
                'descripcion' => 'Pantalones jeans de corte moderno',
                'precio' => 49.99,
                'stock' => 80,
                'imagen' => 'pantalones_jeans.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Bolso de Mano',
                'descripcion' => 'Bolso elegante para uso diario',
                'precio' => 39.99,
                'stock' => 120,
                'imagen' => 'bolso_mano.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Chaqueta de Cuero',
                'descripcion' => 'Chaqueta de cuero auténtico para un look moderno',
                'precio' => 199.99,
                'stock' => 30,
                'imagen' => 'chaqueta_cuero.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Sandalias de Verano',
                'descripcion' => 'Sandalias cómodas y frescas para el verano',
                'precio' => 24.99,
                'stock' => 90,
                'imagen' => 'sandalias_verano.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Gorro de Invierno',
                'descripcion' => 'Gorro de lana para mantenerte caliente',
                'precio' => 14.99,
                'stock' => 200,
                'imagen' => 'gorro_invierno.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Traje de Baño',
                'descripcion' => 'Traje de baño cómodo y estilizado',
                'precio' => 34.99,
                'stock' => 60,
                'imagen' => 'traje_bano.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botines de Cuero',
                'descripcion' => 'Botines elegantes de cuero para el otoño',
                'precio' => 89.99,
                'stock' => 40,
                'imagen' => 'botines_cuero.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Cinturón de Cuero',
                'descripcion' => 'Cinturón de cuero genuino para hombre',
                'precio' => 29.99,
                'stock' => 100,
                'imagen' => 'cinturon_cuero.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Blusa de Seda',
                'descripcion' => 'Blusa elegante de seda para ocasiones especiales',
                'precio' => 69.99,
                'stock' => 70,
                'imagen' => 'blusa_seda.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Gafas de Sol',
                'descripcion' => 'Gafas de sol modernas y elegantes',
                'precio' => 49.99,
                'stock' => 150,
                'imagen' => 'gafas_sol.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Chaqueta de Invierno',
                'descripcion' => 'Chaqueta gruesa y cálida para el invierno',
                'precio' => 129.99,
                'stock' => 20,
                'imagen' => 'chaqueta_invierno.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Pantalones Cortos',
                'descripcion' => 'Pantalones cortos cómodos para el verano',
                'precio' => 39.99,
                'stock' => 110,
                'imagen' => 'pantalones_cortos.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botas de Invierno',
                'descripcion' => 'Botas resistentes y cálidas para el invierno',
                'precio' => 159.99,
                'stock' => 25,
                'imagen' => 'botas_invierno.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bufanda de Seda',
                'descripcion' => 'Bufanda elegante de seda para ocasiones especiales',
                'precio' => 39.99,
                'stock' => 80,
                'imagen' => 'bufanda_seda.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Camisa de Rayas',
                'descripcion' => 'Camisa de rayas clásica para uso diario',
                'precio' => 34.99,
                'stock' => 90,
                'imagen' => 'camisa_rayas.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido Casual',
                'descripcion' => 'Vestido cómodo y casual para el día a día',
                'precio' => 49.99,
                'stock' => 60,
                'imagen' => 'vestido_casual.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Sandalias Elegantes',
                'descripcion' => 'Sandalias elegantes para ocasiones especiales',
                'precio' => 44.99,
                'stock' => 70,
                'imagen' => 'sandalias_elegantes.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Cartera de Mano',
                'descripcion' => 'Cartera de mano elegante y espaciosa',
                'precio' => 89.99,
                'stock' => 40,
                'imagen' => 'cartera_mano.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Pantalones de Tela',
                'descripcion' => 'Pantalones de tela cómodos y elegantes',
                'precio' => 54.99,
                'stock' => 85,
                'imagen' => 'pantalones_tela.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Blusa Casual',
                'descripcion' => 'Blusa cómoda y fresca para el verano',
                'precio' => 29.99,
                'stock' => 100,
                'imagen' => 'blusa_casual.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botas de Cuero',
                'descripcion' => 'Botas de cuero elegantes para el otoño',
                'precio' => 109.99,
                'stock' => 30,
                'imagen' => 'botas_cuero.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Gorro de Lana',
                'descripcion' => 'Gorro de lana suave y cálido para el invierno',
                'precio' => 19.99,
                'stock' => 150,
                'imagen' => 'gorro_lana.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Traje Formal',
                'descripcion' => 'Traje formal completo para ocasiones especiales',
                'precio' => 299.99,
                'stock' => 15,
                'imagen' => 'traje_formal.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Verano',
                'descripcion' => 'Vestido ligero y fresco para el verano',
                'precio' => 59.99,
                'stock' => 75,
                'imagen' => 'vestido_verano.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Zapatillas de Moda',
                'descripcion' => 'Zapatillas modernas y cómodas para el día a día',
                'precio' => 69.99,
                'stock' => 120,
                'imagen' => 'zapatillas_moda.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bolso de Noche',
                'descripcion' => 'Bolso elegante para ocasiones nocturnas',
                'precio' => 79.99,
                'stock' => 50,
                'imagen' => 'bolso_noche.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Chaqueta de Denim',
                'descripcion' => 'Chaqueta de denim clásica y atemporal',
                'precio' => 89.99,
                'stock' => 60,
                'imagen' => 'chaqueta_denim.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Pantalones de Chándal',
                'descripcion' => 'Pantalones cómodos de chándal para hacer ejercicio',
                'precio' => 39.99,
                'stock' => 100,
                'imagen' => 'pantalones_chandal.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botines Elegantes',
                'descripcion' => 'Botines elegantes para ocasiones especiales',
                'precio' => 99.99,
                'stock' => 40,
                'imagen' => 'botines_elegantes.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bufanda de Algodón',
                'descripcion' => 'Bufanda ligera de algodón para el verano',
                'precio' => 24.99,
                'stock' => 80,
                'imagen' => 'bufanda_algodon.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Camisa de Cuadros',
                'descripcion' => 'Camisa de cuadros clásica y cómoda',
                'precio' => 34.99,
                'stock' => 90,
                'imagen' => 'camisa_cuadros.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Fiesta',
                'descripcion' => 'Vestido elegante para fiestas y eventos',
                'precio' => 89.99,
                'stock' => 50,
                'imagen' => 'vestido_fiesta.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Sandalias de Cuero',
                'descripcion' => 'Sandalias de cuero cómodas y elegantes',
                'precio' => 54.99,
                'stock' => 70,
                'imagen' => 'sandalias_cuero.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Cartera Elegante',
                'descripcion' => 'Cartera elegante y espaciosa para uso diario',
                'precio' => 99.99,
                'stock' => 40,
                'imagen' => 'cartera_elegante.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Pantalones de Vestir',
                'descripcion' => 'Pantalones de vestir elegantes para ocasiones formales',
                'precio' => 64.99,
                'stock' => 85,
                'imagen' => 'pantalones_vestir.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Blusa de Encaje',
                'descripcion' => 'Blusa elegante de encaje para ocasiones especiales',
                'precio' => 74.99,
                'stock' => 70,
                'imagen' => 'blusa_encaje.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botas de Moda',
                'descripcion' => 'Botas modernas y elegantes para el otoño',
                'precio' => 119.99,
                'stock' => 30,
                'imagen' => 'botas_moda.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Gorro de Invierno Elegante',
                'descripcion' => 'Gorro de invierno elegante y cálido',
                'precio' => 24.99,
                'stock' => 150,
                'imagen' => 'gorro_invierno_elegante.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Traje de Oficina',
                'descripcion' => 'Traje formal completo para la oficina',
                'precio' => 349.99,
                'stock' => 15,
                'imagen' => 'traje_oficina.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Gala',
                'descripcion' => 'Vestido de gala elegante para eventos formales',
                'precio' => 199.99,
                'stock' => 20,
                'imagen' => 'vestido_gala.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Zapatillas de Running',
                'descripcion' => 'Zapatillas de running cómodas y ligeras',
                'precio' => 89.99,
                'stock' => 120,
                'imagen' => 'zapatillas_running.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bolso de Mano Elegante',
                'descripcion' => 'Bolso de mano elegante y sofisticado',
                'precio' => 109.99,
                'stock' => 50,
                'imagen' => 'bolso_mano_elegante.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Chaqueta de Cuero Elegante',
                'descripcion' => 'Chaqueta de cuero elegante y moderna',
                'precio' => 219.99,
                'stock' => 30,
                'imagen' => 'chaqueta_cuero_elegante.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Pantalones de Algodón',
                'descripcion' => 'Pantalones de algodón cómodos y frescos',
                'precio' => 44.99,
                'stock' => 100,
                'imagen' => 'pantalones_algodon.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botines de Moda',
                'descripcion' => 'Botines de moda elegantes y cómodos',
                'precio' => 109.99,
                'stock' => 40,
                'imagen' => 'botines_moda.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bufanda de Invierno',
                'descripcion' => 'Bufanda de invierno suave y cálida',
                'precio' => 29.99,
                'stock' => 150,
                'imagen' => 'bufanda_invierno.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Camisa de Seda',
                'descripcion' => 'Camisa elegante de seda para ocasiones especiales',
                'precio' => 89.99,
                'stock' => 60,
                'imagen' => 'camisa_seda.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Cóctel',
                'descripcion' => 'Vestido de cóctel elegante para fiestas',
                'precio' => 79.99,
                'stock' => 50,
                'imagen' => 'vestido_coctel.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Sandalias de Moda',
                'descripcion' => 'Sandalias de moda cómodas y elegantes',
                'precio' => 59.99,
                'stock' => 70,
                'imagen' => 'sandalias_moda.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Cartera de Mano de Cuero',
                'descripcion' => 'Cartera de mano de cuero elegante y duradera',
                'precio' => 129.99,
                'stock' => 40,
                'imagen' => 'cartera_mano_cuero.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Pantalones de Lino',
                'descripcion' => 'Pantalones de lino frescos y cómodos para el verano',
                'precio' => 49.99,
                'stock' => 85,
                'imagen' => 'pantalones_lino.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Blusa de Algodón',
                'descripcion' => 'Blusa de algodón cómoda y fresca para el día a día',
                'precio' => 34.99,
                'stock' => 100,
                'imagen' => 'blusa_algodon.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botas de Invierno Elegantes',
                'descripcion' => 'Botas de invierno elegantes y cálidas',
                'precio' => 139.99,
                'stock' => 25,
                'imagen' => 'botas_invierno_elegantes.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Gorro de Invierno de Lana',
                'descripcion' => 'Gorro de invierno de lana suave y cálido',
                'precio' => 19.99,
                'stock' => 150,
                'imagen' => 'gorro_invierno_lana.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Traje de Gala',
                'descripcion' => 'Traje de gala completo para eventos formales',
                'precio' => 399.99,
                'stock' => 15,
                'imagen' => 'traje_gala.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Novia',
                'descripcion' => 'Vestido de novia elegante y exclusivo',
                'precio' => 999.99,
                'stock' => 5,
                'imagen' => 'vestido_novia.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Zapatillas de Moda',
                'descripcion' => 'Zapatillas de moda cómodas y con estilo',
                'precio' => 79.99,
                'stock' => 120,
                'imagen' => 'zapatillas_moda.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bolso de Mano de Tela',
                'descripcion' => 'Bolso de mano de tela elegante y ligero',
                'precio' => 59.99,
                'stock' => 50,
                'imagen' => 'bolso_mano_tela.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Chaqueta de Plumas',
                'descripcion' => 'Chaqueta de plumas cálida y ligera para el invierno',
                'precio' => 159.99,
                'stock' => 30,
                'imagen' => 'chaqueta_plumas.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Pantalones de Cuero',
                'descripcion' => 'Pantalones de cuero elegantes y modernos',
                'precio' => 129.99,
                'stock' => 40,
                'imagen' => 'pantalones_cuero.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botines de Invierno',
                'descripcion' => 'Botines de invierno cómodos y cálidos',
                'precio' => 109.99,
                'stock' => 30,
                'imagen' => 'botines_invierno.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Bufanda de Invierno Elegante',
                'descripcion' => 'Bufanda de invierno elegante y suave',
                'precio' => 34.99,
                'stock' => 80,
                'imagen' => 'bufanda_invierno_elegante.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Camisa de Algodón',
                'descripcion' => 'Camisa de algodón cómoda y fresca para el día a día',
                'precio' => 39.99,
                'stock' => 90,
                'imagen' => 'camisa_algodon.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Vestido de Fiesta Elegante',
                'descripcion' => 'Vestido de fiesta elegante y sofisticado',
                'precio' => 89.99,
                'stock' => 50,
                'imagen' => 'vestido_fiesta_elegante.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Sandalias de Verano',
                'descripcion' => 'Sandalias de verano cómodas y frescas',
                'precio' => 49.99,
                'stock' => 70,
                'imagen' => 'sandalias_verano.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Cartera de Mano Elegante',
                'descripcion' => 'Cartera de mano elegante y espaciosa',
                'precio' => 109.99,
                'stock' => 40,
                'imagen' => 'cartera_mano_elegante.jpg',
                'linea_producto_id' => 4
            ],
            [
                'nombre' => 'Pantalones de Vestir Elegantes',
                'descripcion' => 'Pantalones de vestir elegantes y cómodos',
                'precio' => 74.99,
                'stock' => 85,
                'imagen' => 'pantalones_vestir_elegantes.jpg',
                'linea_producto_id' => 1
            ],
            [
                'nombre' => 'Blusa de Seda Elegante',
                'descripcion' => 'Blusa de seda elegante y sofisticada',
                'precio' => 89.99,
                'stock' => 70,
                'imagen' => 'blusa_seda_elegante.jpg',
                'linea_producto_id' => 2
            ],
            [
                'nombre' => 'Botas de Moda Elegantes',
                'descripcion' => 'Botas de moda elegantes y cómodas',
                'precio' => 119.99,
                'stock' => 30,
                'imagen' => 'botas_moda_elegantes.jpg',
                'linea_producto_id' => 3
            ],
            [
                'nombre' => 'Gorro de Invierno Elegante',
                'descripcion' => 'Gorro de invierno elegante y cálido',
                'precio' => 24.99,
                'stock' => 150,
                'imagen' => 'gorro_invierno_elegante.jpg',
                'linea_producto_id' => 4
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('lineas_producto');
    }
};
