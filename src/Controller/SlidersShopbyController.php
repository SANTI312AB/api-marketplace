<?php

namespace App\Controller;

use App\Entity\BloquesPromocionales;
use App\Entity\Destacados;
use App\Entity\GeneralesApp;
use App\Entity\Productos;
use App\Entity\Sliders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SlidersShopbyController extends AbstractController
{
    #[Route('/sliders', name: 'app_sliders_shopby', methods:['GET'])]
    #[OA\Tag(name: 'AdminUrl')]
    #[OA\Response(
        response: 200,
        description: 'Lista de Slider para el front end de Shopby.'
    )]
    public function index( EntityManagerInterface $entityManager): Response
    {
        $data_url= $entityManager->getRepository(GeneralesApp::class)->findOneBy(['nombre'=>'admin','atributoGeneral'=>'Url']);

        $sliders= $entityManager->getRepository(Sliders::class)->findBy([], ['order_slider'=> 'ASC']);
        $slider_data=[];
        foreach ($sliders as $slider){
          
            $slider_data[]=[
                'id'=>$slider->getId(),
                'mobile'=>$slider->getMovilSlider() ? $data_url->getValorGeneral().'/'.$slider->getMovilSlider():'',
                'desktop'=>$slider->getDesktopSlider() ? $data_url->getValorGeneral().'/'.$slider->getDesktopSlider():'',
                'href'=>$slider->getHrefSlider() ?  $slider->getHrefSlider():'',
                'id_tienda'=>$slider->getIdTienda(),
                'slug_producto_destacado'=>$slider->getSlugProductoDestacado(),
            ];
        }

        return $this->json($slider_data);
    }


    #[Route('/bloques', name: 'app_bloques_shopby', methods: ['GET'])]
    #[OA\Tag(name: 'AdminUrl')]
    #[OA\Response(
        response: 200,
        description: 'Lista de bloques página principal'
    )]
    public function bloques_index(UrlGeneratorInterface $router,Request $request,EntityManagerInterface $entityManager): Response
    {
        // Obtener los bloques ordenados por el campo "orden"
        $bloques = $entityManager->getRepository(BloquesPromocionales::class)->findBy(['visible'=> true], ['orden' => 'ASC']);
       
        $bloque_data = [];

        foreach ($bloques as $bloque) {

            $id_producto= $bloque->getFutureProduct() ? $bloque->getFutureProduct():NULL;

            $id_categoria = $bloque->getCategoria() ? $bloque->getCategoria():NULL;

            $host = $router->getContext()->getBaseUrl();
            $domain = $request->getSchemeAndHttpHost();
    
            
            if ($id_producto) {
                $producto = $entityManager->getRepository(Productos::class)->findOneBy(['id' => $id_producto]);
            } else {
                $producto = $entityManager->getRepository(Productos::class)->productos_randon_bloque($id_categoria);
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
        } elseif($precioRebajadoProducto != null) {
        // Calcula el porcentaje de descuento.
        $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
        $porcentajeDescuento = $porcentajeDescuento;
        }else{
        $porcentajeDescuento = 0;
        }
    
        if ($fecha_edicion = NULL){
        $fecha_edicion= $producto->getFechaEdicion();
        }else{
        $fecha_edicion = $producto->getFechaRegistroProducto();
        }
        $imagenesArray = [];
        
        foreach ($producto-> getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain.$host.'/public/productos/'.$galeria-> getUrlProductoGaleria(),
               
            ];
        }
    
        $categoriasArray= [];
    
        foreach ($producto-> getCategorias() as $categoria) {
            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(), 
                'slug'=>$categoria->getSlug()                 
            ];
        }
    
        $subcategoriasArray = [];
        
        foreach ($producto-> getSubcategorias() as $subcategoria) {
            $subcategoriasArray[] = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre(),     
                'slug'=>$subcategoria->getSlug()              
            ];
        }
    
        $variacionesArray=[];
        $contadorVariaciones = 0;
    
        foreach ($producto->getVariaciones() as $variacion) {
             
            $contadorVariaciones++;
    
        }
    
        $imagenesArray = [];
    
        foreach ($producto->getProductosGalerias() as $galeria) {
            $imagenesArray[] = [
                'id' => $galeria->getId(),
                'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
            ];
        }
    
    
        $total=0;
        $count=0;
        $promedio=null;
    
        foreach ($producto->getProductosComentarios() as $comentario) {
            
            $calificacion = $comentario->getCalificacion();
            if ($calificacion !== null && $calificacion !== '') {
                $total += $calificacion;
                $count++;
            }
    
            $promedio = $count > 0 ? $total / $count : null;
    
    
        }
    

    
        $variacionesArray = [];
        $contadorVariaciones = count($producto->getVariaciones());
    
        
        $productoArray = [
            'id' => $producto->getId(),
            'nombre_producto' => $producto->getNombreProducto(),
                    'slug_producto' => $producto->getSlugProducto(),
                    'precio_normal_producto' => $precioNormalProducto,
                    'precio_rebajado_producto' => $precioRebajadoProducto,
                    'porcentaje_descuento'=>$porcentajeDescuento,
                    'entrgas_nombre'=>$producto->getEntrgasTipo()->getTipo(),
                    'entregas'=>$producto->getEntrgasTipo()->getId(),
                    'productos_ventas_nombre'=>$producto->getProductosVentas()->getTipoVenta(),
                    'productos_ventas'=>$producto->getProductosVentas()->getId(),
                    'estado_nombre'=>$producto->getEstado()->getNobreEstado(),
                    'estado'=>$producto->getEstado()->getId(),
                    'marcas_nombre'=>$marcas,
                    'marcas'=>$marca_id,
                    'cantidad_producto'=>$producto->getCantidadProducto(),
                    'descripcion_corta_producto'=>$producto->getDescripcionCortaProducto(),
                    'descripcion_larga_producto'=>$producto->getDescripcionLargaProducto(),
                    'sku_producto'=>$producto->getSkuProducto(),
                    'ean_producto'=>$producto->getEanProducto(),
                    'video_producto'=>$producto->getVideoProducto(),
                    'galeria'=>$imagenesArray,
                    'categorias'=>$categoriasArray,
                    'subcategorias'=>$subcategoriasArray,
                    'garantia_producto'=>$producto->getGarantiaProducto(),
                    'etiquetas_producto'=>$producto->getEtiquetasProducto(),
                    'regateo_producto'=>$producto->isRegateoProducto(),
                    'ficha_tecnica'=>$producto->getFichaTecnica(),
                    'direcciones'=>$id_direcciones,
                    'fecha_registro_producto'=>$producto->getFechaRegistroProducto(),
                    'fecha_edicion'=>$fecha_edicion,      
                    'variable'=>$producto->isVariable(),  
                    'calificacion'=>$promedio,
                    'numero_variaciones'=>$variacionesArray[] = $contadorVariaciones
            
        ];

        $productosBloque = $bloque->getProductos()->toArray();
        $productosBloque = array_slice($productosBloque, 0, 20,);
        if (count($productosBloque) > 0) {
            // Mostrar productos de la tabla intermedia
            $productosArray = $this->procesarProductos($productosBloque, $domain, $host);
        } else {
            // Obtener productos aleatorios por categoría o globales
            $productosAleatorios = $entityManager->getRepository(Productos::class)->productos_categorias_bloque($id_categoria);
            $productosArray = $this->procesarProductos($productosAleatorios, $domain, $host);
        }
       
    
            $bloque_data[] = [
                'title' => $bloque->getTitle(),
                'categoria' => $bloque->getCategoria() ? $bloque->getCategoria()->getNombre() : '',
                'producto' => $productoArray,
                'random' => $bloque->isRandom() ? $bloque->isRandom() : null,
                'orientacion' => $bloque->getOrientacion() ? $bloque->getOrientacion():'',
                'href' => $bloque->getHref()? $bloque->getHref() : '',
                'productos'=> $productosArray
               
            ];
        }

        return $this->json($bloque_data);
    }


    private function procesarProductos($productos, $domain, $host)
    {
        $productosArray = [];

        foreach ($productos as $producto) {

            if (
                ($producto->isDisponibilidadProducto() ?? false) &&
                !($producto->isBorrador() ?? false) &&
                !($producto->isSuspendido() ?? false)
            ) {
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
                    $porcentajeDescuento = $porcentajeDescuento;
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

                $variacionesArray = [];
                $contadorVariaciones = 0;

                foreach ($producto->getVariaciones() as $variacion) {

                    $contadorVariaciones++;

                }

                $imagenesArray = [];

                foreach ($producto->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'id' => $galeria->getId(),
                        'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
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



                $variacionesArray = [];
                $contadorVariaciones = count($producto->getVariaciones());

                $productosArray[] = [
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
                    'subcategorias' => $subcategoriasArray,
                    'garantia_producto' => $producto->getGarantiaProducto(),
                    'etiquetas_producto' => $producto->getEtiquetasProducto(),
                    'regateo_producto' => $producto->isRegateoProducto(),
                    'ficha_tecnica' => $producto->getFichaTecnica(),

                    'direcciones' => $id_direcciones,
                    'fecha_registro_producto' => $producto->getFechaRegistroProducto(),
                    'fecha_edicion' => $fecha_edicion,
                    'variable' => $producto->isVariable(),
                    'calificacion' => $promedio,
                    'numero_variaciones' => $variacionesArray[] = $contadorVariaciones
                ];


            }
        }

        return $productosArray;
    }



    #[Route('/productos_destacados', name: 'app_productos_destacados', methods:['GET'])]
    #[OA\Tag(name: 'AdminUrl')]
    #[OA\Response(
        response: 200,
        description: 'Lista de bloques página principal'
    )]
    public function p_destacados(UrlGeneratorInterface $router, Request $request, EntityManagerInterface $entityManager): Response
    {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();
        $destacados = $entityManager->getRepository(Destacados::class)->findBy([], ['orden' => 'ASC']);
        $destacadosArray = [];
    
        foreach ($destacados as $destacado) {
            $productoArray = [];
    
            if ($destacado->getProducto() !== null) {
                $producto = $destacado->getProducto();
                $precioNormalProducto = $producto->getPrecioNormalProducto();
                $precioRebajadoProducto = $producto->getPrecioRebajadoProducto();
                
                // Verificar si el href contiene información de la variante
                $href = $destacado->getHref();
                $variantId = null;
    
                if ($href) {
                    $urlParts = parse_url($href);
                    if (isset($urlParts['query'])) {
                        parse_str($urlParts['query'], $queryParams);
                        $variantId = $queryParams['variant'] ?? null;
                    }
                }
    
                // Buscar el precio de la variante si variantId está definido
                if ($variantId !== null) {
                    foreach ($producto->getVariaciones() as $variacion) {
                        if ($variacion->getId() == $variantId) {
                            $precioNormalProducto = $variacion->getPrecio();
                            $precioRebajadoProducto = $variacion->getPrecioRebajado();
                            break;
                        }
                    }
                }
    
                // Calcular porcentaje de descuento
                $porcentajeDescuento = 0;
                if ($precioNormalProducto > 0 && $precioRebajadoProducto !== null) {
                    $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
                }
    
                // Construir array de imágenes
                $imagenesArray = [];
                foreach ($producto->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'id' => $galeria->getId(),
                        'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),
                    ];
                }
    
                // Construir array del producto
                $productoArray = [
                    'id' => $producto->getId(),
                    'slug_producto' => $producto->getSlugProducto(),
                    'precio_normal_producto' => $precioNormalProducto,
                    'precio_rebajado_producto' => $precioRebajadoProducto,
                    'porcentaje_descuento' => $porcentajeDescuento,
                    'galeria' => $imagenesArray,
                ];
            }
    
            // Construir el array del destacado
            $destacadosArray[] = [
                'titulo' => $destacado->getTitulo(),
                'descripcion' => $destacado->getDescripcion(),
                'icono' => $destacado->getIcono() ? $domain . $host . '/public/destacado/icono/' . $destacado->getIcono() : '',
                'imagen' => $destacado->getImagen() ? $domain . $host . '/public/destacado/imagen/' . $destacado->getImagen() : '',
                'href' => $href ?? '',
                'producto' => $productoArray,
                'tienda' => $destacado->getProducto()->getTienda() ? $destacado->getProducto()->getTienda()->getNombreTienda() : '',
            ];
        }
    
        return $this->json($destacadosArray);
    }
     

}
