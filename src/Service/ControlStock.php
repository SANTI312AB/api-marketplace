<?php 

namespace App\Service;

use App\Entity\Productos;
use App\Entity\Variaciones;

class ControlStock{

    public function control_stock(Productos $producto , ?Variaciones $variacion,$cantidad){

        $errors = [];

        // Si se proporciona una variante, validamos contra el stock de la variante.
        if ($variacion !== null) {
            $cantidad_variante = $variacion->getCantidad();
            $terminosNombres = []; // Array para almacenar solo los nombres de los términos

            // Recopilar los nombres de los términos
            foreach ($variacion->getTerminos() as $termino){
                 $terminosNombres[] = $termino->getNombre();
            }

            // Unir los nombres de los términos en una cadena
            $terminosString = '';
            if (!empty($terminosNombres)) {
                $terminosString = ' (' . implode(', ', $terminosNombres) . ')';
            }

            // Construir el nombre del producto con los términos si existen
            $nombreCompletoProducto = $producto->getNombreProducto() . $terminosString;


            if ($cantidad_variante <= 0) {
                $errors[] = 'No hay stock disponible para - ' . $nombreCompletoProducto;
            } elseif ($cantidad > $cantidad_variante) {
                $errors[] = 'No hay suficiente stock para  - ' . $nombreCompletoProducto;
            }

        } else {
            // Si no se proporciona una variante, validamos contra el stock del producto base.
            $cantidad_producto_base = $producto->getCantidadProducto();

            if ($cantidad_producto_base <= 0) {
                $errors[] = 'No hay stock disponible para - ' . $producto->getNombreProducto();
            } elseif ($cantidad > $cantidad_producto_base) {
                $errors[] = 'No hay suficiente stock para - ' . $producto->getNombreProducto();
            }
        }

        return $errors;
    }
    
}