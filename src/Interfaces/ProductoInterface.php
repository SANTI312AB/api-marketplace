<?php

namespace App\Interfaces;

use App\Entity\DetallePedido;
use App\Entity\Productos;
use App\Entity\ProductosComentarios;
use App\Entity\Tiendas;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductoInterface{

    private $request;
    private $router;

    private $em;


    public function __construct(RequestStack $request,UrlGeneratorInterface $router,EntityManagerInterface $em){

        $this->request = $request->getCurrentRequest(); 
        $this->router = $router;
        $this->em= $em;
    }

    public function lista_publica(Productos $producto){

        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();

        $precioNormalProducto = $producto->getPrecioNormalProducto();
        $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();
        $marca_id = $producto->getMarcas() ? $producto->getMarcas()->getId() : null;
        $marcas = $producto->getMarcas() ? $producto->getMarcas()->getNombreM() : null;
        $id_direcciones = $producto->getDirecciones() ? $producto->getDirecciones()->getId() : null;

        $fecha_edicion = $producto->getFechaEdicion();

        // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
        if ($precioNormalProducto <= 0) {
            $porcentajeDescuento = 0;
        } elseif ($precioRebajadoProducto != null) {
            // Calcula el porcentaje de descuento.
            $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
            $porcentajeDescuento = round($porcentajeDescuento,2);
        } else {
            $porcentajeDescuento = 0;
        }

        if ($fecha_edicion = NULL) {
            $fecha_edicion = $producto->getFechaEdicion();
        } else {
            $fecha_edicion = $producto->getFechaRegistroProducto();
        }
        $imagenesArray = [];

        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

            ];
        }

        $categoriasArray = [];

        foreach ($producto->getCategorias() as $c) {
            $categoriasArray[] = [
                'id' => $c->getId(),
                'nombre' => $c->getNombre(),
                'slug' => $c->getSlug()
            ];
        }

        $subcategoriasArray = [];

        foreach ($producto->getSubcategorias() as $s) {
            $subcategoriasArray[] = [
                'id' => $s->getId(),
                'nombre' => $s->getNombre(),
                'slug' => $s->getSlug()
            ];
        }

        $categorias_tiendas_array = [];
        foreach ($producto->getCategoriasTiendas() as $categoria_tienda) {
            $subcategoriasTiendasArray = [];

            foreach ($producto->getSubcategoriasTiendas() as $subcategoriasTienda) {

               $subcategoriasTiendasArray[] = [
                   'id' => $subcategoriasTienda->getId(),
                   'nombre' => $subcategoriasTienda->getNombre(),
                   'slug' => $subcategoriasTienda->getSlug()
               ];
            }

            $categorias_tiendas_array[] = [
                'id' => $categoria_tienda->getId(),
                'nombre' => $categoria_tienda->getNombre(),
                'subcategoriasTiendas'=>$subcategoriasTiendasArray,

            ];
        }

        $total = 0;
        $count = 0;
        $promedio = null;

        foreach ($producto->getProductosComentarios() as $comentario) {

            $calificacion = $comentario->getCalificacion();
            if ($calificacion !== null && $calificacion !== '') {
                $total += $calificacion;
                $count++;
            }

            $promedio = $count > 0 ? $total / $count : null;


        }

        // Lista variantes de los productos agrupados por atributos

        $agrupadosPorAtributo = [];

        foreach ($producto->getVariaciones() as $variacion) {
            $precio = $variacion->getPrecio();
            $precio_rebajado_variante = $variacion->getPrecioRebajado();

            if ($precio <= 0) {
                $porcentajeDescuento_variacion = 0;
            } elseif ($precio_rebajado_variante != null) {
                // Calcula el porcentaje de descuento.
                $porcentajeDescuento_variacion = (($precio - $precio_rebajado_variante) / $precio) * 100;
                $porcentajeDescuento_variacion = round($porcentajeDescuento_variacion,2);
            } else {
                $porcentajeDescuento_variacion = 0;
            }

            $variacionesGaleria = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $variacionesGaleria[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            foreach ($variacion->getTerminos() as $termino) {
                $atributoId = $termino->getAtributos()->getId();

                $variacionData = [
                    'id' => $variacion->getId(),
                    'sku' => $variacion->getSku(),
                    'precio' => $variacion->getPrecio(),
                    'precio_rebajado' => $variacion->getPrecioRebajado(),
                    'descuento' => $porcentajeDescuento_variacion,
                    'cantidad' => $variacion->getCantidad(),
                    'descripcion' => $variacion->getDescripcion(),
                    'variacionesGalerias' => $variacionesGaleria
                ];

                $terminoData = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo(),
                    'variaciones' => [$variacionData]
                ];

                if (!isset($agrupadosPorAtributo[$atributoId])) {
                    $agrupadosPorAtributo[$atributoId] = [
                        'id_atributo' => $atributoId,
                        'nombre_atributo' => $termino->getAtributos()->getNombre(),
                        'terminos' => []
                    ];
                }

                $termExists = false;
                foreach ($agrupadosPorAtributo[$atributoId]['terminos'] as &$existingTermino) {
                    if ($existingTermino['id'] === $terminoData['id']) {
                        $termExists = true;
                        $existingTermino['variaciones'][] = $variacionData;
                    }
                }

                if (!$termExists) {
                    $agrupadosPorAtributo[$atributoId]['terminos'][] = $terminoData;
                }
            }
        }

        $agrupadosPorAtributo = array_values($agrupadosPorAtributo);


        $productosArray= [
            'id' => $producto->getId(),
            'nombre_producto' => $producto->getNombreProducto(),
            'slug_producto' => $producto->getSlugProducto(),
            'precio_normal_producto' => $precioNormalProducto,
            'precio_rebajado_producto' => $precioRebajadoProducto,
            'porcentaje_descuento' => $porcentajeDescuento,
            'entrgas_nombre' => $producto->getEntrgasTipo()->getTipo(),
            'entregas' => $producto->getEntrgasTipo()->getId(),
            'productos_ventas_nombre' => $producto->getProductosVentas()->getTipoVenta(),
            'productos_ventas' => $producto->getProductosVentas()->getId(),
            'productos_tipo'=>$producto->getProductosTipo()? $producto->getProductosTipo()->getTipo() : '',
            'estado_nombre' => $producto->getEstado()->getNobreEstado(),
            'estado' => $producto->getEstado()->getId(),
            'marcas_nombre' => $marcas,
            'marcas' => $marca_id,
            'cantidad_producto' => $producto->getCantidadProducto(),
            'galeria' => $imagenesArray,
            'categorias' => $categoriasArray,
            'subcategorias' => $subcategoriasArray,
            'categoriasTiendas' => $categorias_tiendas_array,
            'regateo_producto' => $producto->isRegateoProducto(),
            'direcciones' => $id_direcciones,
            'fecha_registro_producto' => $producto->getFechaRegistroProducto(),
            'fecha_edicion' => $fecha_edicion,
            'variable' => $producto->isVariable(),
            'calificacion' => $promedio,
            'atributos' => $agrupadosPorAtributo

        ];

        return $productosArray;
    }

    public function vista_publica(Productos $producto){

        if ($producto->getProductosTipo() && $producto->getProductosTipo()->getId() === 4) {
           return null;
        }

        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();


        $precioNormalProducto = $producto->getPrecioNormalProducto();
        $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();
        $productos_ventas = $producto->getProductosVentas() ? $producto->getProductosVentas()->getId() : null;
        $marca_id = $producto->getMarcas() ? $producto->getMarcas()->getId() : null;
        $marcas = $producto->getMarcas() ? $producto->getMarcas()->getNombreM() : null;
        $id_direcciones = $producto->getDirecciones() ? $producto->getDirecciones()->getId() : null;


        // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
        if ($precioNormalProducto <= 0) {
            $porcentajeDescuento = 0;
        } elseif ($precioRebajadoProducto != null) {
            // Calcula el porcentaje de descuento.
            $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;

            $porcentajeDescuento = round($porcentajeDescuento, 2) ;
        } else {
            $porcentajeDescuento = 0;
        }



        $imagenesArray = [];

        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

            ];
        }


        $categoriasArray = [];

        foreach ($producto->getCategorias() as $categoria) {
            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug()
            ];
        }

        $categorias_tiendas_array = [];
        foreach ($producto->getCategoriasTiendas() as $categoria_tienda) {
            $subcategoriasTiendasArray = [];

            foreach ($producto->getSubcategoriasTiendas() as $subcategoriasTienda) {

               $subcategoriasTiendasArray[] = [
                   'id' => $subcategoriasTienda->getId(),
                   'nombre' => $subcategoriasTienda->getNombre(),
                   'slug' => $subcategoriasTienda->getSlug()
               ];
            }

            $categorias_tiendas_array[] = [
                'id' => $categoria_tienda->getId(),
                'nombre' => $categoria_tienda->getNombre(),
                'subcategoriasTiendas'=>$subcategoriasTiendasArray,

            ];
        }

        $subcategoriasArray = [];

        foreach ($producto->getSubcategorias() as $subcategoria) {
            $subcategoriasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre(),
                'slug' => $subcategoria->getSlug()
            ];
        }

         
       


        $agrupadosPorAtributo = [];

        foreach ($producto->getVariaciones() as $variacion) {
            $precio = $variacion->getPrecio();
            $precio_rebajado_variante = $variacion->getPrecioRebajado();

            if ($precio <= 0) {
                $porcentajeDescuento_variacion = 0;
            } elseif ($precio_rebajado_variante != null) {
                // Calcula el porcentaje de descuento.
                $porcentajeDescuento_variacion = (($precio - $precio_rebajado_variante) / $precio) * 100;
                $porcentajeDescuento_variacion = round($porcentajeDescuento_variacion,2);
            } else {
                $porcentajeDescuento_variacion = 0;
            }

            $variacionesGaleria = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $variacionesGaleria[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            foreach ($variacion->getTerminos() as $termino) {
                $atributoId = $termino->getAtributos()->getId();

                $variacionData = [
                    'id' => $variacion->getId(),
                    'sku' => $variacion->getSku(),
                    'precio' => $variacion->getPrecio(),
                    'precio_rebajado' => $variacion->getPrecioRebajado(),
                    'descuento' =>$porcentajeDescuento_variacion ,
                    'cantidad' => $variacion->getCantidad(),
                    'descripcion' => $variacion->getDescripcion(),
                    'variacionesGalerias' => $variacionesGaleria
                ];

                $terminoData = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo(),
                    'variaciones' => [$variacionData]
                ];

                if (!isset($agrupadosPorAtributo[$atributoId])) {
                    $agrupadosPorAtributo[$atributoId] = [
                        'id_atributo' => $atributoId,
                        'nombre_atributo' => $termino->getAtributos()->getNombre(),
                        'terminos' => []
                    ];
                }

                $termExists = false;
                foreach ($agrupadosPorAtributo[$atributoId]['terminos'] as &$existingTermino) {
                    if ($existingTermino['id'] === $terminoData['id']) {
                        $termExists = true;
                        $existingTermino['variaciones'][] = $variacionData;
                        break;
                    }
                }

                if (!$termExists) {
                    $agrupadosPorAtributo[$atributoId]['terminos'][] = $terminoData;
                }
            }

            // Ordenar los términos alfabéticamente por nombre dentro de cada grupo
            foreach ($agrupadosPorAtributo as &$grupo) {
                usort($grupo['terminos'], function ($a, $b) {
                    return $a['nombre'] <=> $b['nombre'] ?: strcmp($a['nombre'], $b['nombre']);
                });
            }


        }

        $agrupadosPorAtributo = array_values($agrupadosPorAtributo);
        $t = $producto->getTienda()->getId();
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['id' => $t]);
        $comentarios = $this->em->getRepository(ProductosComentarios::class)->comentarios_tienda($tienda);
        $total = 0;
        $count = 0;
        foreach ($comentarios as $comentario) {
            $calificacion = $comentario->getCalificacion();
            if ($calificacion !== null && $calificacion !== '') {
                $total += $calificacion;
                $count++;
            }
        }

        $promedio = $count > 0 ? $total / $count : null;


        $avatar = '';
        if ($producto->getTienda()->getLogin()->getUsuarios() && $producto->getTienda()->getLogin()->getUsuarios()->getAvatar() !== null) {
            $avatar = $domain . $host . '/public/user/selfie/' . $producto->getTienda()->getLogin()->getUsuarios()->getAvatar();
        }

        $tipo_documento = $producto->getTienda()->getLogin()->getUsuarios()->getTipoDocumento();

        if ($producto->getTienda()->getLogin()->getUsuarios()->getDni() !== null) {

            if ($tipo_documento === 'CI' || $tipo_documento === 'PPN') {
                $apellido = $producto->getTienda()->getLogin()->getUsuarios()->getApellido();
            } else {
                $apellido = null;
            }
        } else {
            $apellido = $apellido = $producto->getTienda()->getLogin()->getUsuarios()->getApellido();
        }

        $giftcar_data=[];

        if ($producto->getProductosTipo() && $producto->getProductosTipo()->getId() === 3) {

            $giftcar_data=[
                'nombre'=> $producto->getDescripcionLargaProducto(),
                'correo'=>$producto->getDescripcionCortaProducto()
            ];
        }else{
            $giftcar_data=[
                'nombre'=> null,
                'correo'=>null
            ];
        }


        $productosArray = [
            'id' => $producto->getId(),
            'vendedor' => [
                'avatar' => $avatar,
                'username' => $producto->getTienda()->getLogin()->getUsername(),
                'nombre' => $producto->getTienda()->getLogin()->getUsuarios()->getNombre(),
                'apellido' => $apellido,
                'tienda_verificada' => $producto->getTienda()->getEstado() ? $producto->getTienda()->getEstado()->getNobreEstado() === 'VERIFICADO' : false,
                'tienda_publicada' => $producto->getTienda()->isVisibilidadTienda(),
                'calificacion' => $promedio,
                'total_calificaiones' => $count
            ],
            'nombre_producto' => $producto->getNombreProducto(),
            'slug_producto' => $producto->getSlugProducto(),
            'precio_normal_producto' => $precioNormalProducto,
            'precio_rebajado_producto' => $precioRebajadoProducto,
            'porcentaje_descuento' =>$porcentajeDescuento,
            'entrgas_nombre' => $producto->getEntrgasTipo()->getTipo(),
            'entregas' => $producto->getEntrgasTipo()->getId(),
            'productos_ventas_nombre' => $producto->getProductosVentas()->getTipoVenta(),
            'productos_tipo'=>$producto->getProductosTipo()? $producto->getProductosTipo()->getTipo() : '',
            'productos_ventas' => $productos_ventas,
            'estado_nombre' => $producto->getEstado()->getNobreEstado(),
            'estado' => $producto->getEstado()->getId(),
            'marcas_nombre' => $marcas,
            'marcas' => $marca_id,
            'cantidad_producto' => $producto->getCantidadProducto(),
            'descripcion_corta_producto' => $producto->getDescripcionCortaProducto(),
            'descripcion_larga_producto' => $producto->getDescripcionLargaProducto(),
            'sku_producto' => $producto->getSkuProducto(),
            'ean_producto' => $producto->getEanProducto(),
            'video_producto' => $producto->getVideoProducto(),
            'galeria' => $imagenesArray,
            'categorias' => $categoriasArray,
            'categoriasTiendas' => $categorias_tiendas_array,
            'subcategorias' => $subcategoriasArray,
            'garantia_producto' => $producto->getGarantiaProducto(),
            'etiquetas_producto' => $producto->getEtiquetasProducto(),
            'regateo_producto' => $producto->isRegateoProducto(),
            'ficha_tecnica' => $producto->getFichaTecnica(),
            'ubicacion' => [
                'id_ciudad' => $id_direcciones,
                'ciudad' => ($producto->getDirecciones() && $producto->getDirecciones()->getCiudad()) ? $producto->getDirecciones()->getCiudad()->getCiudad() : '',
            ],
            'dimensiones' => [
                'alto' => $producto->getAlto(),
                'ancho' => $producto->getAncho(),
                'largo' => $producto->getLargo(),
                'peso' => $producto->getPeso()
            ],
            'fecha_registro_producto' => $producto->getFechaRegistroProducto(),
            'fecha_edicion' => $producto->getFechaEdicion(),
            'variable' => $producto->isVariable(),
            'atributos' => $agrupadosPorAtributo,
            'gift_card'=>$giftcar_data,
            'tiempo_entrega'=>$producto->getTiempoEntrega(),
        ];    

        return $productosArray;

        
    }

    public function lista_privada(Productos $producto){

        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();
        $n_pedidos = $this->em->getRepository(DetallePedido::class)->n_ventas_producto($producto->getId());

        $numero_ventas = 0;
        foreach ($n_pedidos as $n) {
            $numero_ventas++;
        }

        $precioNormalProducto = $producto->getPrecioNormalProducto();
        $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();
        $marca_id = $producto->getMarcas() ? $producto->getMarcas()->getId() : null;
        $marcas = $producto->getMarcas() ? $producto->getMarcas()->getNombreM() : null;
        $id_direcciones = $producto->getDirecciones() ? $producto->getDirecciones()->getId() : null;

        $fecha_edicion = $producto->getFechaEdicion();

        // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
        if ($precioNormalProducto <= 0) {
            $porcentajeDescuento = 0;
        } elseif ($precioRebajadoProducto != null) {
            // Calcula el porcentaje de descuento.
            $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
        } else {
            $porcentajeDescuento = 0;
        }

        if ($fecha_edicion = NULL) {
            $fecha_edicion = $producto->getFechaEdicion();
        } else {
            $fecha_edicion = $producto->getFechaRegistroProducto();
        }
        $imagenesArray = [];

        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

            ];
        }

        $categoriasArray = [];

        foreach ($producto->getCategorias() as $categoria) {
            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre()
            ];
        }



        $subcategoriasArray = [];

        foreach ($producto->getSubcategorias() as $subcategoria) {
            $subcategoriasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre()
            ];
        }


        $categorias_tiendas_array = [];
        foreach ($producto->getCategoriasTiendas() as $categoria_tienda) {

            $subcategoriasTiendasArray = [];

            foreach ($producto->getSubcategoriasTiendas() as $subcategoriasTienda) {

               $subcategoriasTiendasArray[] = [
                   'id' => $subcategoriasTienda->getId(),
                   'nombre' => $subcategoriasTienda->getNombre(),
                   'slug' => $subcategoriasTienda->getSlug()
               ];
            }
            $categorias_tiendas_array[] = [
                'id' => $categoria_tienda->getId(),
                'nombre' => $categoria_tienda->getNombre(),
                'subcategoriasTiendas'=> $subcategoriasTiendasArray

            ];
        }


      

        $variacionesArray = [];
        foreach ($producto->getVariaciones() as $variacion) {

            $variacionesGaleria = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $variacionesGaleria[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            $terminosArray = [];

            foreach ($variacion->getTerminos() as $termino) {

                $terminosArray[] = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'atributos' => $termino->getAtributos()->getId(),
                    'nombre_atributo' => $termino->getAtributos()->getNombre()
                ];
            }

            $variacionesArray[] = [

                'id' => $variacion->getId(),
                'sku' => $variacion->getSku(),
                'precio' => $variacion->getPrecio(),
                'precio_rebajado' => $variacion->getPrecioRebajado(),
                'cantidad' => $variacion->getCantidad(),
                'descripcion' => $variacion->getDescripcion(),
                'terminos' => $terminosArray,
                'variacionesGalerias' => $variacionesGaleria

            ];

        }


        $productosArray= [
            'id' => $producto->getId(),
            'nombre_producto' => $producto->getNombreProducto(),
            'slug_producto' => $producto->getSlugProducto(),
            'precio_normal_producto' => $precioNormalProducto,
            'precio_rebajado_producto' => $precioRebajadoProducto,
            'porcentaje_descuento' =>round($porcentajeDescuento,2) ,
            'entrgas_nombre' => $producto->getEntrgasTipo()->getTipo(),
            'entregas' => $producto->getEntrgasTipo()->getId(),
            'productos_ventas_nombre' => $producto->getProductosVentas()->getTipoVenta(),
            'productos_tipo'=>$producto->getProductosTipo()? $producto->getProductosTipo()->getTipo() : '',
            'productos_ventas' => $producto->getProductosVentas()->getId(),
            'estado_nombre' => $producto->getEstado()->getNobreEstado(),
            'estado' => $producto->getEstado()->getId(),
            'marcas_nombre' => $marcas,
            'marcas' => $marca_id,
            'cantidad_producto' => $producto->getCantidadProducto(),
            'descripcion_corta_producto' => $producto->getDescripcionCortaProducto(),
            'descripcion_larga_producto' => $producto->getDescripcionLargaProducto(),
            'sku_producto' => $producto->getSkuProducto(),
            'ean_producto' => $producto->getEanProducto(),
            'video_producto' => $producto->getVideoProducto(),
            'galeria' => $imagenesArray,
            'categorias' => $categoriasArray,
            'categoriasTiendas' => $categorias_tiendas_array,
            'subcategorias' => $subcategoriasArray,
            'garantia_producto' => $producto->getGarantiaProducto(),
            'etiquetas_producto' => $producto->getEtiquetasProducto(),
            'regateo_producto' => $producto->isRegateoProducto(),
            'direcciones' => $id_direcciones,
            'fecha_registro_producto' => $producto->getFechaRegistroProducto(),
            'fecha_edicion' => $fecha_edicion,
            'variable' => $producto->isVariable(),
            'disponibilidad_producto' => $producto->isDisponibilidadProducto(),
            'variaciones' => $variacionesArray,
            'cantidad_ventas' => $numero_ventas,
            'borrador'=>$producto->isBorrador(),

        ];

        return $productosArray;
        

    }

    public function vista_privada(Productos $producto){

        $domain = $this->request->getSchemeAndHttpHost(); 
        $host = $this->router->getContext()->getBaseUrl();
        $precioNormalProducto = $producto->getPrecioNormalProducto();
        $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();
        $marca_id = $producto->getMarcas() ? $producto->getMarcas()->getId() : null;
        $marcas = $producto->getMarcas() ? $producto->getMarcas()->getNombreM() : null;
        $id_direcciones = $producto->getDirecciones() ? $producto->getDirecciones()->getId() : null;


        // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
        if ($precioNormalProducto <= 0) {
            $porcentajeDescuento = 0;
        } elseif ($precioRebajadoProducto != null) {
            // Calcula el porcentaje de descuento.
            $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;

            $porcentajeDescuento = round($porcentajeDescuento,2);
        } else {
            $porcentajeDescuento = 0;
        }



        $imagenesArray = [];

        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

            ];
        }


        $categoriasArray = [];

        foreach ($producto->getCategorias() as $categoria) {
            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre()
            ];
        }

        $subcategoriasArray = [];

        foreach ($producto->getSubcategorias() as $subcategoria) {
            $subcategoriasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre()
            ];
        }

        

        $categorias_tiendas_array = [];
        foreach ($producto->getCategoriasTiendas() as $categoria_tienda) {

            $subcategoriasTiendasArray = [];

            foreach ($producto->getSubcategoriasTiendas() as $subcategoriasTienda) {

               $subcategoriasTiendasArray[] = [
                   'id' => $subcategoriasTienda->getId(),
                   'nombre' => $subcategoriasTienda->getNombre(),
                   'slug' => $subcategoriasTienda->getSlug()
               ];
            }
            $categorias_tiendas_array[] = [
                'id' => $categoria_tienda->getId(),
                'nombre' => $categoria_tienda->getNombre(),
                'subcategoriasTiendas'=>$subcategoriasTiendasArray

            ];
        }

        $variacionesArray = [];
        foreach ($producto->getVariaciones() as $variacion) {

            $precio = $variacion->getPrecio();
            $precio_rebajado_variante = $variacion->getPrecioRebajado();

            if ($precio <= 0) {
                $porcentajeDescuento_variacion = 0;
            } elseif ($precio_rebajado_variante != null) {
                // Calcula el porcentaje de descuento.
                $porcentajeDescuento_variacion = (($precio - $precio_rebajado_variante) / $precio) * 100;

                $porcentajeDescuento_variacion = round($porcentajeDescuento_variacion,2);
            } else {
                $porcentajeDescuento_variacion = 0;
            }

            $variacionesGaleria = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $variacionesGaleria[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            $terminosArray = [];

            foreach ($variacion->getTerminos() as $termino) {

                $terminosArray[] = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'atributos' => $termino->getAtributos()->getId(),
                    'nombre_atributo' => $termino->getAtributos()->getNombre()
                ];
            }

            $variacionesArray[] = [

                'id' => $variacion->getId(),
                'sku' => $variacion->getSku(),
                'precio' => $variacion->getPrecio(),
                'precio_rebajado' => $variacion->getPrecioRebajado(),
                'descuento' =>$porcentajeDescuento_variacion,
                'cantidad' => $variacion->getCantidad(),
                'descripcion' => $variacion->getDescripcion(),
                'codigo_variante' => $variacion->getCodigoVariante() ?? '',
                'terminos' => $terminosArray,
                'variacionesGalerias' => $variacionesGaleria
            ];

        }

        $subastasArray= [];

        foreach ($producto->getSubastas() as $subasta){
            $subastasArray []=[
                'id'=>$subasta->getId(),
                'inicio_subasta'=>$subasta->getInicioSubasta(),
                'fin_subasta'=>$subasta->getFinSubasta(),
                'valor_minimo'=>$subasta->getValorMinimo(),
                'IdVariacion' => $subasta->getIdVariacion() ? $subasta->getIdVariacion() :'',
                'activo'=>$subasta->isActivo(),
            ];
        }

        $regateosArray = [];
        foreach ($producto->getRegateos() as $regateo){
            $regateosArray []=[
                'id'=>$regateo->getId(),
                'fecha_registro'=>$regateo->getFecha(),
                'fecha_edicion'=>$regateo->getFechaEdicion(),
                'estado'=>$regateo->getEstado(),
                'regateo'=>$regateo->getRegateo(),
            ];
        }

        $productosArray = [
            'id' => $producto->getId(),
            'nombre_producto' => $producto->getNombreProducto(),
            'slug_producto' => $producto->getSlugProducto(),
            'precio_normal_producto' => $precioNormalProducto,
            'precio_rebajado_producto' => $precioRebajadoProducto,
            'porcentaje_descuento' =>$porcentajeDescuento,
            'entrgas_nombre' => $producto->getEntrgasTipo() ?  $producto->getEntrgasTipo()->getTipo():'',
            'entrgas_tipo' => $producto->getEntrgasTipo() ? $producto->getEntrgasTipo()->getId():'',
            'productos_ventas_nombre' =>$producto->getProductosVentas() ?  $producto->getProductosVentas()->getTipoVenta():'',
            'productos_ventas' =>$producto->getProductosVentas() ? $producto->getProductosVentas()->getId():'',
            'estado_nombre' =>$producto->getEstado() ? $producto->getEstado()->getNobreEstado():'',
            'productos_tipo'=>$producto->getProductosTipo()? $producto->getProductosTipo()->getTipo() : '',
            'estado' =>$producto->getEstado() ? $producto->getEstado()->getId():'',
            'marcas_nombre' => $marcas,
            'marcas' => $marca_id,
            'cantidad_producto' => $producto->getCantidadProducto(),
            'descripcion_corta_producto' => $producto->getDescripcionCortaProducto(),
            'descripcion_larga_producto' => $producto->getDescripcionLargaProducto(),
            'sku_producto' => $producto->getSkuProducto(),
            'ean_producto' => $producto->getEanProducto(),
            'video_producto' => $producto->getVideoProducto(),
            'galeria' => $imagenesArray,
            'categorias' => $categoriasArray,
            'categoriasTiendas' => $categorias_tiendas_array,
            'subcategorias' => $subcategoriasArray,
            'garantia_producto' => $producto->getGarantiaProducto(),
            'etiquetas_producto' => $producto->getEtiquetasProducto(),
            'regateo_producto' => $producto->isRegateoProducto(),
            'ficha_tecnica' => $producto->getFichaTecnica(),
            'direcciones' => $id_direcciones,
            'fecha_registro_producto' => $producto->getFechaRegistroProducto(),
            'fecha_edicion' => $producto->getFechaEdicion(),
            'dimensiones' => [
                'alto' => $producto->getAlto(),
                'ancho' => $producto->getAncho(),
                'largo' => $producto->getLargo(),
                'peso' => $producto->getPeso()
            ],
            'variable' => $producto->isVariable(),
            'disponibilidad_producto' => $producto->isDisponibilidadProducto(),
            'impuestos_incluidos' => $producto->isImpuestosIncluidos(),
            'tiene_iva' => $producto->isTieneIva(),
            'variaciones' => $variacionesArray,
            'subastas'=> $subastasArray,
            'borrador'=>$producto->isBorrador(),
            'regateos'=>$regateosArray,
            'tiempo_entrega'=>$producto->getTiempoEntrega(),
            'codigo_producto'=>$producto->getCodigoProducto() ?? '',
        ];

        return $productosArray;
    }


    public function vista_minima(   Productos $producto){
        $domain = $this->request->getSchemeAndHttpHost();
        $host = $this->router->getContext()->getBaseUrl();
        $precioNormalProducto = $producto->getPrecioNormalProducto();
        $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();

        if ($precioNormalProducto <= 0) {
            $porcentajeDescuento = 0;
        } elseif ($precioRebajadoProducto != null) {
            // Calcula el porcentaje de descuento.
            $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
            $porcentajeDescuento = round($porcentajeDescuento, 2);
        } else {
            $porcentajeDescuento = 0;
        }


        $imagenesArray = [];

        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

            ];
        }

        $categoriasArray = [];

        foreach ($producto->getCategorias() as $categoria) {
            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug()
            ];
        }

        $subcategoriasArray = [];

        foreach ($producto->getSubcategorias() as $subcategoria) {
            $subcategoriasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre(),
                'slug' => $subcategoria->getSlug()
            ];
        }

        $data = [
            'id' => $producto->getId(),
            'nombre_producto' => $producto->getNombreProducto(),
            'slug_producto' => $producto->getSlugProducto(),
            'precio_normal_producto' => $precioNormalProducto,
            'precio_rebajado_producto' => $precioRebajadoProducto,
            'porcentaje_descuento' => $porcentajeDescuento,
            'galeria' => $imagenesArray,
            'categorias' => $categoriasArray,
            'subcategorias' => $subcategoriasArray
        ];

        return $data;
    }
        
}