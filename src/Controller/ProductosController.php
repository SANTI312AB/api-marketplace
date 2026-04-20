<?php

namespace App\Controller;

use App\Entity\Categorias;
use App\Entity\Ciudades;
use App\Entity\DetallePedido;
use App\Entity\EntregasTipo;
use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Pedidos;
use App\Entity\Productos;
use App\Entity\ProductosComentarios;
use App\Entity\ProductosFavoritos;
use App\Entity\ProductosGaleria;
use App\Entity\ProductosMarcas;
use App\Entity\ProductosTipo;
use App\Entity\Subcategorias;
use App\Entity\Tiendas;
use App\Entity\Usuarios;
use App\Entity\Variaciones;
use App\Entity\VariacionesGaleria;
use App\Form\DeleteMultipleProductosType;
use App\Form\EditProductosGaleriaType;
use App\Form\MultiProductsType;
use App\Form\ProductosComentariosType;
use App\Form\ProductosGaleriaType;
use App\Form\ProductosType;
use App\Form\VariacionesType;
use App\Form\VariacionGaleriaEditType;
use App\Form\VariacionGaleriaType;
use App\Interfaces\ErrorsInterface;
use App\Interfaces\ProductoInterface;
use App\Repository\CategoriasRepository;
use App\Repository\CategoriasTiendaRepository;
use App\Repository\DetallePedidoRepository;
use App\Repository\ProductosComentariosRepository;
use App\Repository\ProductosFavoritosRepository;
use App\Repository\ProductosRepository;
use App\Repository\SubcategoriasRepository;
use App\Repository\TiendasRepository;
use App\Repository\VariacionesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use DateTime;

class ProductosController extends AbstractController
{

    private $errorsInterface;
    private $productoInterface;

    public function __construct(ErrorsInterface $errorsInterface, ProductoInterface $productoInterface)
    {
        $this->errorsInterface = $errorsInterface;
        $this->productoInterface = $productoInterface;
    }



    #[Route('/search_product', name: 'search_product', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de busqeda de productos ',
    )]
    #[OA\Parameter(
        name: "search",
        in: "query",
        description: "Buscar productos por nombre,categoria,subcategoria,tienda y marca."
    )]
    public function buscar_producto(Request $request, UrlGeneratorInterface $router, ProductosRepository $productosRepository): Response
    {
        $allowedParams = ['search'];

        // Verificar si hay parámetros no permitidos
        $queryParams = array_keys($request->query->all());
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $producto = $request->query->get('search');

        if ($producto == null || $producto == '') {
            return $this->errorsInterface->error_message('El campo esta vacio', Response::HTTP_BAD_REQUEST);
        }

        $productos = $productosRepository->search_product($producto);

        $data = [];
        foreach ($productos as $producto) {

            $data[] = $this->productoInterface->vista_minima($producto);
        }

        return $this->json($data);
    }


    #[Route('/all_tiendas', name: 'all_tiendas', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Lista de tiendas verificadas',
    )]
    public function index_tiendas(Request $request, TiendasRepository $tiendasRepository, UrlGeneratorInterface $router, EntityManagerInterface $entityManager): Response
    {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();

        $tiendas = $tiendasRepository->all_tiendas();
        $data = [];
        foreach ($tiendas as $tienda) {

            $categoriasArray = [];
            foreach ($tienda->getCategoriasTiendas() as $categoria) {
                $categoriasArray[] = [
                    'nombre' => $categoria->getNombre(),
                    'slug' => $categoria->getSlug(),
                ];
            }

            $nombre_tienda = $tienda->getNombreTienda() ? $tienda->getNombreTienda() : null;
            $nombre_usuario = $tienda->getLogin()->getUsuarios() ? $tienda->getLogin()->getUsuarios()->getNombre() : '';

            if ($nombre_tienda) {
                $dato = $nombre_tienda;
            } else {
                $dato = $nombre_usuario;
            }

            $data[] = [
                'nombre' => $dato,
                'cover' => $tienda->getCover() ? $domain . $host . '/public/tiendas/' . $tienda->getCover() : '',
                'avatar' => $domain . $host . '/public/user/selfie/' . $tienda->getLogin()->getUsuarios()->getAvatar(),
                'slug' => $tienda->getSlug(),
                'main' => $tienda->getMain() ? $domain . $host . '/public/tiendas/' . $tienda->getMain() : '',
                'categorias' => $categoriasArray,
            ];
        }


        return $this->json($data);

    }


    #[Route('/tienda/{slug}', name: 'ver_tienda', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Ver una tienda por Slug',
    )]
    public function viewTienda(Request $request, $slug, TiendasRepository $tiendasRepository, UrlGeneratorInterface $router, EntityManagerInterface $entityManager): Response
    {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost(); // Asegúrate de que tu controlador tenga el método getRequest() para obtener la solicitud actual.

        $tienda = $tiendasRepository->findOneBy(['slug' => $slug]);

        if (!$tienda) {
            return $this->errorsInterface->error_message('Tienda no encontrada', Response::HTTP_NOT_FOUND);
        }

        if ($tienda && $tienda->isVisible() == false) {
            return $this->errorsInterface->error_message('Tienda no esta visible', Response::HTTP_BAD_REQUEST);
        }

        $galeriaArray = [];
        foreach ($tienda->getGaleriaTiendas() as $galeria) {
            $galeriaArray[] = [
                'id' => $galeria->getId(),
                'url' => $galeria->getUrl() ? $domain . $host . '/public/tiendas/' . $galeria->getUrl() : '',
                'seccion' => $galeria->getSeccion(),
            ];
        }

        $galeriasAgrupadas = array_reduce($galeriaArray, function ($resultado, $item) {
            $seccion = $item['seccion'];
            if (!array_key_exists($seccion, $resultado)) {
                $resultado[$seccion] = [];
            }
            $resultado[$seccion][] = $item;
            return $resultado;
        }, []);

        $categoriasArray = [];
        foreach ($tienda->getCategoriasTiendas() as $categoria) {

            $subcategoriasTiendasArray = [];

            foreach ($categoria->getSubcategoriasTiendas() as $subcategoria) {

                $subcategoriasTiendasArray[] = [
                    'id' => $subcategoria->getId(),
                    'nombre' => $subcategoria->getNombre(),
                    'slug' => $subcategoria->getSlug(),
                    'image' => $subcategoria->getImagen() ? $domain . $host . '/public/subcategorias/' . $subcategoria->getImagen() : ''
                ];
            }

            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug(),
                'banner' => $categoria->getBanner() ? $domain . $host . '/public/categorias/' . $categoria->getBanner() : '',
                'img' => $categoria->getImagen() ? $domain . $host . '/public/categorias/' . $categoria->getImagen() : '',
                'subcategorias' => $subcategoriasTiendasArray
            ];
        }
        $dato = null;

        $nombre_tienda = $tienda->getNombreTienda() ? $tienda->getNombreTienda() : null;
        $nombre_usuario = $tienda->getLogin()->getUsuarios() ? $tienda->getLogin()->getUsuarios()->getNombre() : '';

        if ($nombre_tienda) {
            $dato = $nombre_tienda;
        } else {
            $dato = $nombre_usuario;
        }

        $data = [
            'avatar' => $tienda->getLogin()->getUsuarios() ? $domain . $host . '/public/user/selfie/' . $tienda->getLogin()->getUsuarios()->getAvatar() : '',
            'username' => $tienda->getLogin()->getUsername(),
            'nombre' => $dato,
            'cover' => $tienda->getCover() ? $domain . $host . '/public/tiendas/' . $tienda->getCover() : '',
            'main' => $tienda->getMain() ? $domain . $host . '/public/tiendas/' . $tienda->getMain() : '',
            'slug' => $tienda->getSlug(),
            'verificado' => $tienda->getEstado() ? $tienda->getEstado()->getNobreEstado() === 'VERIFICADO' : false,
            'galeria' => $galeriasAgrupadas,
            'categorias' => $categoriasArray,
            'visible' => $tienda->isVisible() ? $tienda->isVisible() : ''
        ];

        return $this->json($data);
    }


    #[Route('/tienda/categorias/{slug}', name: 'tienda_categorias', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Filtro de categorias por slug de una tienda',
    )]
    public function tiendas_categorias($slug, EntityManagerInterface $entityManager, Request $request, CategoriasTiendaRepository $categoriasRepository, UrlGeneratorInterface $router): Response
    {
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();

        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 4); // Número predeterminado de elementos por página
        $offset = $request->query->getInt('offset', 0);


        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['slug' => $slug]);

        $categorias = $categoriasRepository->findBy(['tienda' => $tienda]);
        $startProductIndex = ($page - 1) * $perPage + $offset;

        $categorias = array_slice($categorias, $startProductIndex, $perPage);
        $categoriasArray = [];
        foreach ($categorias as $categoria) {

            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug(),
                'slider-image' => $categoria->getImagen() ? $domain . $host . '/public/categorias/' . $categoria->getImagen() : '',
            ];

        }

        return $this->json($categoriasArray);
    }


    #[Route('/productos/relacionados/{slug}', name: 'productos_relacionados', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos relacionados por categoria, subcategoria, tienda',
    )]
    public function p_relacionados($slug, Request $request, ProductosRepository $productosRepository, UrlGeneratorInterface $router): Response
    {
        $p = $productosRepository->findOneBy(['slug_producto' => $slug]);

        if (!$p) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        $slug = $p->getSlugProducto();
        $tienda = $p->getTienda()->getSlug();

        $subcategoria_slug = null;
        foreach ($p->getSubcategorias() as $subcategoria) {
            $subcategoria_slug = $subcategoria->getSlug();
            break;
        }

        $categoria_slug = null;
        foreach ($p->getCategorias() as $categoria) {
            $categoria_slug = $categoria->getSlug();
            break;
        }


        $consultas = [];

        if ($subcategoria_slug !== null) {

            $consultas[] = function () use ($productosRepository, $tienda, $subcategoria_slug, $slug) {
                return $productosRepository->productos_subcategorias_tienda($tienda, $subcategoria_slug, $slug);
            };

        }


        $consultas[] = function () use ($productosRepository, $tienda, $categoria_slug, $slug) {
            return $productosRepository->productos_categoria_tienda($tienda, $categoria_slug, $slug);
        };



        $consultas[] = function () use ($productosRepository, $tienda, $slug) {
            return $productosRepository->productos_vendedor($tienda, $slug);
        };

        $consultas[] = function () use ($productosRepository, $slug, $subcategoria_slug) {
            return $productosRepository->productos_subcategorias($slug, $subcategoria_slug);
        };

        $consultas[] = function () use ($productosRepository, $slug, $categoria_slug) {
            return $productosRepository->productos_categorias($slug, $categoria_slug);
        };

        $consultas[] = function () use ($productosRepository, $slug) {
            return $productosRepository->productos_randon_3($slug);
        };


        $productosArray = [];

        foreach ($consultas as $consulta) {

            $productos = $consulta();
            if (!empty($productos)) {
                foreach ($productos as $producto) {

                    $productosArray[] = $this->productoInterface->lista_publica($producto);
                }
                break;
            }
        }

        return $this->json(['data' => $productosArray]);
    }


    #[Route('/productos/all', name: 'todos_los_productos', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos',
    )]
    #[OA\Parameter(
        name: "query",
        in: "query",
        description: "Buscar productos por nombre,categoria,subcategoria,tienda y marca."
    )]
    #[OA\Parameter(
        name: "per_page",
        in: "query",
        description: "Numero de elementos por pagina."
    )]
    #[OA\Parameter(
        name: "page",
        in: "query",
        description: "Paginador de lista de productos."
    )]
    #[OA\Parameter(
        name: "offset",
        in: "query",
        description: "Determina desde que elemento inicia el arrray de datos."
    )]
    #[OA\Parameter(
        name: "orderBy",
        in: "query",
        description: "Ordena los productos por fecha, precio y de forma aleatoria(precio_desc,fecha_desc,random)."
    )]
    #[OA\Parameter(
        name: "min_precio",
        in: "query",
        description: "Filtra los productos por un precio mayor a igual ."
    )]
    #[OA\Parameter(
        name: "max_precio",
        in: "query",
        description: "Filtra los productos por un precio menor  a igual."
    )]
    #[OA\Parameter(
        name: "estado",
        in: "query",
        description: "Filtra los productos por estado del producto(nuevo,usado,renovado,open_box)."
    )]
    #[OA\Parameter(
        name: "categoria",
        in: "query",
        description: "Filtra los productos por el slug de una categoria"
    )]
    #[OA\Parameter(
        name: "subcategoria",
        in: "query",
        description: "Filtra los productos por el slug de una subcategoria"
    )]
    #[OA\Parameter(
        name: "categoria_tienda",
        in: "query",
        description: "Filtra los productos por el slug de una categoria de una tienda"
    )]
    #[OA\Parameter(
        name: "tienda",
        in: "query",
        description: "Filtra los productos por el slug de una tienda"
    )]
    #[OA\Parameter(
        name: "marca",
        in: "query",
        description: "Filtra los productos por el slug de una marca"
    )]
    #[OA\Parameter(
        name: "tipo_entrega",
        in: "query",
        description: "Filtra los productos por tipo_entrega(a_domicilio,retiro_tienda_fisica)"
    )]
    #[OA\Parameter(
        name: "descuento",
        in: "query",
        description: "Filtra los productos que tengan descuento(booleano)"
    )]
    #[OA\Parameter(
        name: "con_stock",
        in: "query",
        description: "Filtra los productos que tengan stock(booleano)"
    )]
    #[OA\Parameter(
        name: "mas_vendidos",
        in: "query",
        description: "Filtra los productos mas vedidos"
    )]
    #[OA\Parameter(
        name: "subcategoria_tienda",
        in: "query",
        description: "Filtra los productos que esten en una subcategora de tiendas oficiales"
    )]
    #[OA\Parameter(
        name: "productos_tipo",
        in: "query",
        description: "Filtra los productos por tipo (FISICO,DIGITAL,SERVICIO)"
    )]
    #[OA\Parameter(
        name: "ciudad",
        in: "query",
        description: "filtar productos por ciudad"
    )]
    #[OA\Parameter(
        name: "tipo_cobro",
        in: "query",
        description: "Filtra los productos de tipo servicio por el cobro Hora,Día,Mes y Año"
    )]
    #[OA\Parameter(
        name: "terminos",
        in: "query",
        description: "Filtra los productos por uno o varios terminos separados por comas."
    )]
    public function all_products(Request $request, ProductosRepository $productosRepository, EntityManagerInterface $em): Response
    {
        $allowedParams = [
            'page',
            'per_page',
            'offset',
            'orderBy',
            'min_precio',
            'max_precio',
            'estado',
            'categoria',
            'subcategoria',
            'categoria_tienda',
            'subcategoria_tienda',
            'tienda',
            'marca',
            'mas_vendidos',
            'tipo_entrega',
            'descuento',
            'query',
            'con_stock',
            'productos_tipo',
            'ciudad',
            'tipo_cobro',
            'terminos'
        ];

        // Obtener todos los parámetros de la URL
        $queryParams = array_keys($request->query->all());

        // Detectar los que no están permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20); // Número predeterminado de elementos por página
        $offset = $request->query->getInt('offset', 0);


        $orderBy = $request->query->get('orderBy');
        $minPrecio = $request->query->get('min_precio');
        $maxPrecio = $request->query->get('max_precio');
        $estado = $request->query->get('estado');
        $categoria = $request->query->get('categoria');
        $subcategoria = $request->query->get('subcategoria');
        $categoria_tienda = $request->query->get('categoria_tienda');
        $sucategoria_tienda = $request->query->get('subcategoria_tienda');
        $tienda = $request->query->get('tienda');
        $marca = $request->query->get('marca');
        $mas_vendidos = filter_var($request->query->get('mas_vendidos'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('mas_vendidos'), FILTER_VALIDATE_BOOLEAN) : null;
        $entrega_tipo = $request->query->get('tipo_entrega');
        $conDescuento = filter_var($request->query->get('descuento'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('descuento'), FILTER_VALIDATE_BOOLEAN) : null;
        $searchTerm = $request->query->get('query');
        $cantidad = filter_var($request->query->get('con_stock'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('con_stock'), FILTER_VALIDATE_BOOLEAN) : null;
        $productos_tipo = $request->query->get('productos_tipo');
        $ciudad = $request->query->get('ciudad');
        $tipo_cobro = $request->query->get('tipo_cobro');
        $terminosString = $request->query->get('terminos');

        // Convertir a array (si no es nulo)
        $terminos = $terminosString ? explode(',', $terminosString) : [];

        // Limpiar espacios en blanco (opcional)
        $productos = $productosRepository->findProductosWithFilters(['orderBy' => $orderBy], $minPrecio, $maxPrecio, $estado, $categoria, $subcategoria, $tienda, $conDescuento, $searchTerm, $cantidad ? $cantidad : null, $categoria_tienda, $marca, $entrega_tipo, $mas_vendidos, $sucategoria_tienda, $productos_tipo, $ciudad, $tipo_cobro, $terminos);

        // Total de productos sin filtrar
        $totalProducts = count($productos);

        // Filtra solo si se aplican ciertos filtros
        if ($orderBy || $minPrecio || $maxPrecio || $estado || $categoria || $subcategoria) {
            $totalFilteredProducts = $totalProducts;
        } else {
            // Si no se aplican filtros, muestra el total sin paginación
            $totalFilteredProducts = $totalProducts;
        }


        $startProductIndex = ($page - 1) * $perPage + $offset;
        // Aplica limit o paginación
        $productos = array_slice($productos, $startProductIndex, $perPage);



        $productosArray = [];

        foreach ($productos as $producto) {

            $productosArray[] = $this->productoInterface->lista_publica($producto);

        }

        $filters = $this->filtros($em, $productos_tipo, $categoria, $subcategoria);
        return $this->json([
            'data' => $productosArray,
            'total' => $totalFilteredProducts,
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ]);

    }



    private function filtros(EntityManagerInterface $em, $productos_tipo = null, $categoria = null, $subcategoria = null)
    {

        // Inicializamos el array principal que contendrá TODOS los filtros
        $filters = [];

        // --- Lógica para obtener datos (Esta parte se mantiene igual) ---
        $ciudadesConProductos = $em
            ->getRepository(Ciudades::class) // Reemplaza "Ciudad" con tu entidad
            ->ciudades_con_productos();

        $catrgoriasconProductos = $em->getRepository(Categorias::class)->categorias_con_productos($productos_tipo);

        $subcategoriasconProductos = $em->getRepository(Subcategorias::class)->SubcategoriascoProductos($categoria, $productos_tipo);

        // --- Construir filtros individuales y añadirlos al array principal $filters ---

        // Filtro Ciudades
        // Añadimos este filtro directamente al array principal $filters
        // Verificamos si hay datos antes de añadir el filtro
        if (!empty($ciudadesConProductos)) {
            $filters[] = [
                'query' => 'ciudad',
                'title' => 'Ciudad',
                'data' => array_map(function ($item) {
                    // Retornamos un OBJETO para cada item de data
                    return (object) [
                        'name' => $item['ciudad'],
                        'count' => $item['total_productos'],
                        'id' => $item['ciudad']
                    ];
                }, $ciudadesConProductos),
            ];
        }

        // Filtro Categorias
        // Añadimos este filtro directamente al array principal $filters
        if (!empty($catrgoriasconProductos)) {
            $filters[] = [
                'query' => 'categoria',
                'title' => 'Categorias',
                'data' => array_map(function ($item) {
                    // Retornamos un OBJETO para cada item de data
                    return (object) [
                        'name' => $item['nombre'],
                        'count' => $item['total_productos'],
                        'id' => $item['slug']
                    ];
                }, $catrgoriasconProductos),
            ];
        }

        // Filtro Subcategorias
        // Añadimos este filtro directamente al array principal $filters
        if (!empty($subcategoriasconProductos)) {
            $filters[] = [
                'query' => 'subcategoria',
                'title' => 'Subcategorías',
                'data' => array_map(function ($item) {
                    // Retornamos un OBJETO para cada item de data
                    return (object) [
                        'name' => $item['nombre'],
                        'count' => $item['total_productos'],
                        'id' => $item['slug']
                    ];
                }, $subcategoriasconProductos),
            ];
        }

        // --- Lógica condicional para Terminos (Atributos) ---
        // CADA ATRIBUTO SE CONVERTIRÁ EN UN FILTRO INDEPENDIENTE EN $filters

        if ($categoria !== null || $subcategoria !== null) {
            $productos_con_terminos = $em->getRepository(Productos::class)
                ->productos_con_terminos($categoria, $subcategoria);

            // Agrupar términos por atributo (esto sigue igual)
            $grupos_por_atributo = [];
            foreach ($productos_con_terminos as $item) {
                $atributo = $item['atributo'];
                if (!isset($grupos_por_atributo[$atributo])) {
                    $grupos_por_atributo[$atributo] = [];
                }
                $grupos_por_atributo[$atributo][] = $item;
            }

            // Crear un filtro por cada atributo y AÑADIRLO DIRECTAMENTE AL ARRAY PRINCIPAL $filters
            // Ya NO necesitamos un array temporal $filters_terminos para recopilarlos primero.
            foreach ($grupos_por_atributo as $nombre_atributo => $terminos) {
                // Verificamos si hay términos para este atributo antes de añadir el filtro
                if (!empty($terminos)) {
                    // Añadimos CADA filtro de atributo (ej: Color, Memoria) directamente a $filters
                    $filters[] = [ // <-- ¡Aquí está el cambio clave! Añade directamente a $filters
                        'query' => 'terminos', // O considera 'atributo' si es más descriptivo
                        'title' => $nombre_atributo,
                        'data' => array_map(function ($item) {
                            // Retornamos un OBJETO para cada item de data
                            return (object) [
                                'name' => $item['nombre'],
                                'count' => $item['total_productos'],
                                'id' => $item['nombre'] // O considera $item['slug'] si tienes uno para términos
                            ];
                        }, $terminos)
                    ];
                }
            }
            // Después de este bucle, los filtros de cada atributo (Color, Memoria, etc.)
            // están añadidos individualmente al array $filters, al mismo nivel que categoria, etc.
        }

        // --- Lógica condicional para otros filtros (Estados, Entregas, Marcas, Tiendas) ---
        // CADA UNO SE AÑADE DIRECTAMENTE AL ARRAY PRINCIPAL $filters

        if ($productos_tipo !== 'SERVICIO') {
            $estadosConProductos = $em->getRepository(Estados::class)->productos_con_estado();
            // Añadir filtro Estados directamente si hay datos
            if (!empty($estadosConProductos)) {
                $filters[] = [
                    'query' => 'estado',
                    'title' => 'Estados',
                    'data' => array_map(function ($item) {
                        // Retornamos un OBJETO para cada item de data
                        return (object) [
                            'name' => $item['nombre'],
                            'count' => $item['total_productos'],
                            'id' => $item['nombre'] // O considera $item['slug']
                        ];
                    }, $estadosConProductos)
                ];
            }

            $entregasConProductos = $em->getRepository(EntregasTipo::class)->entregas_con_producto();
            // Añadir filtro Entregas directamente si hay datos
            if (!empty($entregasConProductos)) {
                $filters[] = [
                    'query' => 'tipo_entrega',
                    'title' => 'Tipo Entrega',
                    'data' => array_map(function ($item) {
                        // Retornamos un OBJETO para cada item de data
                        return (object) [
                            'name' => $item['nombre'],
                            'count' => $item['total_productos'],
                            'id' => $item['slug'] // O considera $item['nombre']
                        ];
                    }, $entregasConProductos)
                ];
            }

            $marcasConProductos = $em->getRepository(ProductosMarcas::class)->marcas_con_producto($categoria);
            // Añadir filtro Marcas directamente si hay datos
            if (!empty($marcasConProductos)) {
                $filters[] = [
                    'query' => 'marca',
                    'title' => 'Marcas',
                    'data' => array_map(function ($item) {
                        // Retornamos un OBJETO para cada item de data
                        return (object) [
                            'name' => $item['nombre'],
                            'count' => $item['total_productos'],
                            'id' => $item['slug'] // O considera $item['nombre']
                        ];
                    }, $marcasConProductos)
                ];
            }

            $tiendas_con_productos = $em->getRepository(Tiendas::class)->tiendas_con_productos();
            // Añadir filtro Tiendas directamente si hay datos
            if (!empty($tiendas_con_productos)) {
                $filters[] = [
                    'query' => 'tienda',
                    'title' => 'Tiendas',
                    'data' => array_map(function ($item) {
                        // Retornamos un OBJETO para cada item de data
                        return (object) [
                            'name' => $item['nombre'],
                            'count' => $item['total_productos'],
                            'id' => $item['slug'] // O considera $item['nombre']
                        ];
                    }, $tiendas_con_productos),
                ];
            }
        }

        // --- Lógica condicional para Tipo de Cobro ---
        // SE AÑADE DIRECTAMENTE AL ARRAY PRINCIPAL $filters

        if ($productos_tipo == 'SERVICIO') {
            $tipo_cobro = $em->getRepository(Productos::class)->tipo_cobro_producto_servicio();
            // Añadir filtro Tipo Cobro directamente si hay datos
            if (!empty($tipo_cobro)) {
                $filters[] = [
                    'query' => 'tipo_cobro',
                    'title' => 'Tipo de cobro',
                    'data' => array_map(function ($item) {
                        // Retornamos un OBJETO para cada item de data
                        return (object) [
                            'name' => $item['nombre'],
                            'count' => $item['total_productos'],
                            'id' => $item['nombre'] // O considera $item['slug']
                        ];
                    }, $tipo_cobro)
                ];
            }
        }

        return $filters;
    }

    #[Route('/productos/all_tienda', name: 'todos_los_productos_tienda', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos',
    )]
    #[OA\Parameter(
        name: "query",
        in: "query",
        description: "Buscar productos por nombre,categoria,subcategoria,tienda y marca."
    )]
    #[OA\Parameter(
        name: "per_page",
        in: "query",
        description: "Numero de elementos por pagina."
    )]
    #[OA\Parameter(
        name: "page",
        in: "query",
        description: "Paginador de lista de productos."
    )]
    #[OA\Parameter(
        name: "offset",
        in: "query",
        description: "Determina desde que elemento inicia el arrray de datos."
    )]
    #[OA\Parameter(
        name: "orderBy",
        in: "query",
        description: "Ordena los productos por fecha, precio y de forma aleatoria(precio_desc,fecha_desc,random)."
    )]
    #[OA\Parameter(
        name: "min_precio",
        in: "query",
        description: "Filtra los productos por un precio mayor a igual ."
    )]
    #[OA\Parameter(
        name: "max_precio",
        in: "query",
        description: "Filtra los productos por un precio menor  a igual."
    )]
    #[OA\Parameter(
        name: "estado",
        in: "query",
        description: "Filtra los productos por estado del producto(nuevo,usado,renovado,open_box)."
    )]
    #[OA\Parameter(
        name: "categoria",
        in: "query",
        description: "Filtra los productos por el slug de una categoria"
    )]
    #[OA\Parameter(
        name: "subcategoria",
        in: "query",
        description: "Filtra los productos por el slug de una subcategoria"
    )]
    #[OA\Parameter(
        name: "categoria_tienda",
        in: "query",
        description: "Filtra los productos por el slug de una categoria de una tienda"
    )]
    #[OA\Parameter(
        name: "tienda",
        in: "query",
        description: "Filtra los productos por el slug de una tienda"
    )]
    #[OA\Parameter(
        name: "marca",
        in: "query",
        description: "Filtra los productos por el slug de una marca"
    )]
    #[OA\Parameter(
        name: "tipo_entrega",
        in: "query",
        description: "Filtra los productos por tipo_entrega(a_domicilio,retiro_tienda_fisica)"
    )]
    #[OA\Parameter(
        name: "descuento",
        in: "query",
        description: "Filtra los productos que tengan descuento(booleano)"
    )]
    #[OA\Parameter(
        name: "con_stock",
        in: "query",
        description: "Filtra los productos que tengan stock(booleano)"
    )]
    #[OA\Parameter(
        name: "mas_vendidos",
        in: "query",
        description: "Filtra los productos mas vedidos"
    )]
    #[OA\Parameter(
        name: "subcategoria_tienda",
        in: "query",
        description: "Filtra los productos que esten en una subcategora de tiendas oficiales"
    )]
    #[OA\Parameter(
        name: "productos_tipo",
        in: "query",
        description: "Filtra los productos por tipo (FISICO,DIGITAL,SERVICIO)"
    )]
    #[OA\Parameter(
        name: "ciudad",
        in: "query",
        description: "filtar productos por ciudad"
    )]
    #[OA\Parameter(
        name: "tipo_cobro",
        in: "query",
        description: "Filtra los productos de tipo servicio por el cobro Hora,Día,Mes y Año"
    )]
    #[OA\Parameter(
        name: "terminos",
        in: "query",
        description: "Filtra los productos por uno o varios terminos separados por comas."
    )]
    public function all_products_tienda(Request $request, ProductosRepository $productosRepository, EntityManagerInterface $em): Response
    {
         $allowedParams = [
            'page',
            'per_page',
            'offset',
            'orderBy',
            'min_precio',
            'max_precio',
            'estado',
            'categoria',
            'subcategoria',
            'categoria_tienda',
            'subcategoria_tienda',
            'tienda',
            'marca',
            'mas_vendidos',
            'tipo_entrega',
            'descuento',
            'query',
            'con_stock',
            'productos_tipo',
            'ciudad',
            'tipo_cobro',
            'terminos'
        ];

        // Obtener todos los parámetros de la URL
        $queryParams = array_keys($request->query->all());

        // Detectar los que no están permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20); // Número predeterminado de elementos por página
        $offset = $request->query->getInt('offset', 0);


        $orderBy = $request->query->get('orderBy');
        $minPrecio = $request->query->get('min_precio');
        $maxPrecio = $request->query->get('max_precio');
        $estado = $request->query->get('estado');
        $categoria = $request->query->get('categoria');
        $subcategoria = $request->query->get('subcategoria');
        $categoria_tienda = $request->query->get('categoria_tienda');
        $sucategoria_tienda = $request->query->get('subcategoria_tienda');
        $tienda = $request->query->get('tienda');
        $marca = $request->query->get('marca');
        $mas_vendidos = filter_var($request->query->get('mas_vendidos'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('mas_vendidos'), FILTER_VALIDATE_BOOLEAN) : null;
        $entrega_tipo = $request->query->get('tipo_entrega');
        $conDescuento = filter_var($request->query->get('descuento'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('descuento'), FILTER_VALIDATE_BOOLEAN) : null;
        $searchTerm = $request->query->get('query');
        $cantidad = filter_var($request->query->get('con_stock'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('con_stock'), FILTER_VALIDATE_BOOLEAN) : null;
        $productos_tipo = $request->query->get('productos_tipo');
        $ciudad = $request->query->get('ciudad');
        $tipo_cobro = $request->query->get('tipo_cobro');
        $terminosString = $request->query->get('terminos');

        // Convertir a array (si no es nulo)
        $terminos = $terminosString ? explode(',', $terminosString) : [];
        $productos = $productosRepository->findProductosWithFilters_tienda(['orderBy' => $orderBy], $minPrecio, $maxPrecio, $estado, $categoria, $subcategoria, $tienda, $conDescuento, $searchTerm, $cantidad ? $cantidad : null, $categoria_tienda, $marca, $entrega_tipo, $mas_vendidos, $sucategoria_tienda, $productos_tipo, $ciudad, $tipo_cobro, $terminos);

        // Total de productos sin filtrar
        $totalProducts = count($productos);

        // Filtra solo si se aplican ciertos filtros
        if ($orderBy || $minPrecio || $maxPrecio || $estado || $categoria || $subcategoria) {
            $totalFilteredProducts = $totalProducts;
        } else {
            // Si no se aplican filtros, muestra el total sin paginación
            $totalFilteredProducts = $totalProducts;
        }


        // Obtén el índice de inicio directamente desde la solicitud
        $startProductIndex = ($page - 1) * $perPage + $offset;


        // Aplica limit o paginación
        $productos = array_slice($productos, $startProductIndex, $perPage);


        $productosArray = [];

        foreach ($productos as $producto) {

            $productosArray[] = $this->productoInterface->lista_publica($producto);

        }
        $filters = $this->filtros($em, $productos_tipo, $categoria, $subcategoria);

        return $this->json([
            'data' => $productosArray,
            'total' => $totalFilteredProducts,
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ]);

    }



    #[Route('/productos/random', name: 'producto_randon', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Muestra un producto de forma aleatoria',
    )]
    #[OA\Parameter(
        name: "categoria",
        in: "query",
        description: "Producto aleatorio por categoria."
    )]
    #[OA\Parameter(
        name: "subcategoria",
        in: "query",
        description: "Producto aleatorio por subcategoria."
    )]
    #[OA\Parameter(
        name: "tienda",
        in: "query",
        description: "Filtra un producto aleatorio por tienda."
    )]
    public function randon_product(Request $request, ProductosRepository $productosRepository, UrlGeneratorInterface $router): Response
    {
         $allowedParams = [
            'categoria',
            'subcategoria',
            'tienda'
        ];

        // Obtener todos los parámetros de la URL
        $queryParams = array_keys($request->query->all());

        // Detectar los que no están permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $categoria = $request->query->get('categoria');
        $subcategoria = $request->query->get('subcategoria');
        $tienda = $request->query->get('tienda');

        $producto = $productosRepository->randon($categoria, $subcategoria, $tienda);

        if (!$producto) {

            $producto = $productosRepository->randon_2();
        }


        $productosArray = $this->productoInterface->vista_publica($producto);

        return $this->json([
            'data' => $productosArray,
        ]);

    }


    #[Route('/productos/mirar_variacion/{id}', name: 'view_variations', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Mirar variaciones de un producto',
    )]
    public function show_variations_2(Request $request, VariacionesRepository $variacionesRepository, UrlGeneratorInterface $router, Variaciones $variacion = null): Response
    {
        if (!$variacion) {
            return $this->errorsInterface->error_message('Variación no encontrada', Response::HTTP_NOT_FOUND);
        }
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();
        $variaciones = $variacionesRepository->findBy(['id' => $variacion], null, 1);
        $variacionesArray = [];

        foreach ($variaciones as $variacion) {
            $terminosArray = [];
            foreach ($variacion->getTerminos() as $termino) {
                $terminosArray[] = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo(),
                    'atributos' => $termino->getAtributos()->getId(),
                    'nombre_atributo' => $termino->getAtributos()->getNombre()
                ];
            }

            $galeriaArray = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $galeriaArray[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            $variacionesArray = [
                'id' => $variacion->getId(),
                'descripcion' => $variacion->getDescripcion(),
                'precio' => $variacion->getPrecio(),
                'precio_rebajado' => $variacion->getPrecioRebajado(),
                'cantidad' => $variacion->getCantidad(),
                'sku' => $variacion->getSku(),
                'terminos' => $terminosArray,
                'variacionesGalerias' => $galeriaArray
            ];
        }

        return $this->json($variacionesArray);
    }

    #[Route('/productos/ver/{slugger}', name: 'show_productos', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Ver un producto por slugger. Se puede filtrar las variantes con los atributos y los términos de la respuesta del producto así: /producto/ver/{slugger}?Color=Azul&Memoria=64GB',
    )]
    #[OA\Parameter(
        name: "borrador",
        in: "query",
        description: "Muestra un producto en borrador con 0 o 1."
    )]
    public function view_producto_slug(
        Request $request,
        ProductosRepository $productosRepository,
        $slugger = null
    ): Response {
        if ($slugger === null) {
            return $this->errorsInterface->error_message('Parámetro slugger no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $borrador = filter_var($request->query->get('borrador'), FILTER_VALIDATE_BOOLEAN);

        $producto = $productosRepository->findOneBy([
            'slug_producto' => $slugger,
            'borrador' => $borrador,
            'disponibilidad_producto' => true,
            'suspendido' => false
        ]);

        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        $productoArray = $this->productoInterface->vista_publica($producto);

        if ($productoArray === null) {
            return $this->errorsInterface->error_message('Producto no compatible', Response::HTTP_CONFLICT);
        }

        $productoArray['id_variante'] = null;

        // ✅ Lista inicial de parámetros permitidos
        $allowedParams = ['borrador'];

        // Agregar dinámicamente los nombres de atributos permitidos
        if (!empty($productoArray['atributos'])) {
            foreach ($productoArray['atributos'] as $atributo) {
                $allowedParams[] = $atributo['nombre_atributo'];
            }
        }

        // Verificar parámetros no permitidos
        $queryParams = array_keys($request->query->all());
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Obtener solo filtros de variantes (quitamos borrador)
        $filtrosVariante = $request->query->all();
        unset($filtrosVariante['borrador']);

        if ($productoArray['variable'] && !empty($filtrosVariante)) {
            $listasDeIds = [];

            // 1. Recopilar listas de IDs para cada filtro de atributo
            foreach ($filtrosVariante as $nombreAtributoFiltro => $valorTerminoFiltro) {
                $idsEncontradosParaEsteAtributo = [];
                foreach ($productoArray['atributos'] as $atributo) {
                    if ($atributo['nombre_atributo'] === $nombreAtributoFiltro) {
                        foreach ($atributo['terminos'] as $termino) {
                            if ($termino['nombre'] === $valorTerminoFiltro) {
                                $idsEncontradosParaEsteAtributo = array_map(fn($v) => $v['id'], $termino['variaciones']);
                                break;
                            }
                        }
                        break;
                    }
                }
                if (!empty($idsEncontradosParaEsteAtributo)) {
                    $listasDeIds[] = $idsEncontradosParaEsteAtributo;
                }
            }

            // 2. Encontrar la intersección de IDs
            $idVariacionFinal = null;
            if (count($listasDeIds) > 0 && count($listasDeIds) === count($filtrosVariante)) {
                $idsInterseccion = $listasDeIds[0];
                for ($i = 1; $i < count($listasDeIds); $i++) {
                    $idsInterseccion = array_intersect($idsInterseccion, $listasDeIds[$i]);
                }
                if (count($idsInterseccion) === 1) {
                    $idVariacionFinal = reset($idsInterseccion);
                }
            }

            // 3. Buscar la data de la variación y actualizar el producto
            if ($idVariacionFinal !== null) {
                $variacionEncontrada = null;
                foreach ($productoArray['atributos'] as $atributo) {
                    foreach ($atributo['terminos'] as $termino) {
                        foreach ($termino['variaciones'] as $variacion) {
                            if ($variacion['id'] === $idVariacionFinal) {
                                $variacionEncontrada = $variacion;
                                break 3;
                            }
                        }
                    }
                }

                if ($variacionEncontrada) {
                    $nombreVariante = [];
                    foreach ($filtrosVariante as $nombreAtributo => $valorTermino) {
                        $nombreVariante[] = $nombreAtributo . " " . $valorTermino;
                    }

                    if (!empty($nombreVariante)) {
                        $productoArray['nombre_producto'] .= " " . implode(", ", $nombreVariante);
                    }

                    // Actualizar datos de la variación
                    $productoArray['precio_normal_producto'] = $variacionEncontrada['precio'];
                    $productoArray['precio_rebajado_producto'] = $variacionEncontrada['precio_rebajado'] ?? null;
                    $productoArray['porcentaje_descuento'] = $variacionEncontrada['descuento'];
                    $productoArray['cantidad_producto'] = $variacionEncontrada['cantidad'];
                    $productoArray['sku_producto'] = $variacionEncontrada['sku'];
                    $productoArray['id_variante'] = $variacionEncontrada['id'];

                    if (!empty($variacionEncontrada['descripcion'])) {
                        $productoArray['descripcion_corta_producto'] = $variacionEncontrada['descripcion'];
                    }

                    // ✅ Lógica de fallback para la galería de imágenes
                    if (!empty($variacionEncontrada['variacionesGalerias'])) {
                        $productoArray['galeria'] = array_map(function ($galeriaVariante) {
                            return [
                                'id' => $galeriaVariante['id'],
                                'url_producto_galeria' => $galeriaVariante['url_variacion']
                            ];
                        }, $variacionEncontrada['variacionesGalerias']);
                    }
                }
            }
        }

        return $this->json($productoArray);
    }



    #[Route('/productos/show_id/{id}', name: 'show_productos_id', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Ver un producto por id.Se puede filtrar las variantes con los atributos y los terminos de la respuesta del producto asi:/producto/ver/{slugger}?Color=Azul&Memoria=64GB',
    )]
    #[OA\Parameter(
        name: "borrador",
        in: "query",
        description: "Muestra un producto en borrador con 0 o 1."
    )]
    public function view_produc_id(Request $request, ProductosRepository $productosRepository, UrlGeneratorInterface $router, $id = null): Response
    {

        if ($id === null) {
            return $this->errorsInterface->error_message('Parámetro slugger no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $borrador = filter_var($request->query->get('borrador'), FILTER_VALIDATE_BOOLEAN);

        $producto = $productosRepository->findOneBy([
            'id' => $id,
            'borrador' => $borrador,
            'disponibilidad_producto' => true,
            'suspendido' => false
        ]);

        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_NOT_FOUND);
        }

        $productoArray = $this->productoInterface->vista_publica($producto);

        if ($productoArray === null) {
            return $this->errorsInterface->error_message('Producto no compatible', Response::HTTP_CONFLICT);
        }

        $productoArray['id_variante'] = null;

        // ✅ Lista inicial de parámetros permitidos
        $allowedParams = ['borrador'];

        // Agregar dinámicamente los nombres de atributos permitidos
        if (!empty($productoArray['atributos'])) {
            foreach ($productoArray['atributos'] as $atributo) {
                $allowedParams[] = $atributo['nombre_atributo'];
            }
        }

        // Verificar parámetros no permitidos
        $queryParams = array_keys($request->query->all());
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos: ' . implode(', ', $invalidParams),
                Response::HTTP_BAD_REQUEST
            );
        }

        // Obtener solo filtros de variantes (quitamos borrador)
        $filtrosVariante = $request->query->all();
        unset($filtrosVariante['borrador']);

        if ($productoArray['variable'] && !empty($filtrosVariante)) {
            $listasDeIds = [];

            // 1. Recopilar listas de IDs para cada filtro de atributo
            foreach ($filtrosVariante as $nombreAtributoFiltro => $valorTerminoFiltro) {
                $idsEncontradosParaEsteAtributo = [];
                foreach ($productoArray['atributos'] as $atributo) {
                    if ($atributo['nombre_atributo'] === $nombreAtributoFiltro) {
                        foreach ($atributo['terminos'] as $termino) {
                            if ($termino['nombre'] === $valorTerminoFiltro) {
                                $idsEncontradosParaEsteAtributo = array_map(fn($v) => $v['id'], $termino['variaciones']);
                                break;
                            }
                        }
                        break;
                    }
                }
                if (!empty($idsEncontradosParaEsteAtributo)) {
                    $listasDeIds[] = $idsEncontradosParaEsteAtributo;
                }
            }

            // 2. Encontrar la intersección de IDs
            $idVariacionFinal = null;
            if (count($listasDeIds) > 0 && count($listasDeIds) === count($filtrosVariante)) {
                $idsInterseccion = $listasDeIds[0];
                for ($i = 1; $i < count($listasDeIds); $i++) {
                    $idsInterseccion = array_intersect($idsInterseccion, $listasDeIds[$i]);
                }
                if (count($idsInterseccion) === 1) {
                    $idVariacionFinal = reset($idsInterseccion);
                }
            }

            // 3. Buscar la data de la variación y actualizar el producto
            if ($idVariacionFinal !== null) {
                $variacionEncontrada = null;
                foreach ($productoArray['atributos'] as $atributo) {
                    foreach ($atributo['terminos'] as $termino) {
                        foreach ($termino['variaciones'] as $variacion) {
                            if ($variacion['id'] === $idVariacionFinal) {
                                $variacionEncontrada = $variacion;
                                break 3;
                            }
                        }
                    }
                }

                if ($variacionEncontrada) {
                    $nombreVariante = [];
                    foreach ($filtrosVariante as $nombreAtributo => $valorTermino) {
                        $nombreVariante[] = $nombreAtributo . " " . $valorTermino;
                    }

                    if (!empty($nombreVariante)) {
                        $productoArray['nombre_producto'] .= " " . implode(", ", $nombreVariante);
                    }

                    // Actualizar datos de la variación
                    $productoArray['precio_normal_producto'] = $variacionEncontrada['precio'];
                    $productoArray['precio_rebajado_producto'] = $variacionEncontrada['precio_rebajado'] ?? null;
                    $productoArray['porcentaje_descuento'] = $variacionEncontrada['descuento'];
                    $productoArray['cantidad_producto'] = $variacionEncontrada['cantidad'];
                    $productoArray['sku_producto'] = $variacionEncontrada['sku'];
                    $productoArray['id_variante'] = $variacionEncontrada['id'];

                    if (!empty($variacionEncontrada['descripcion'])) {
                        $productoArray['descripcion_corta_producto'] = $variacionEncontrada['descripcion'];
                    }

                    // ✅ Lógica de fallback para la galería de imágenes
                    if (!empty($variacionEncontrada['variacionesGalerias'])) {
                        $productoArray['galeria'] = array_map(function ($galeriaVariante) {
                            return [
                                'id' => $galeriaVariante['id'],
                                'url_producto_galeria' => $galeriaVariante['url_variacion']
                            ];
                        }, $variacionEncontrada['variacionesGalerias']);
                    }
                }
            }
        }

        return $this->json($productoArray);
    }


    #[Route('/api/productos/my_show/{id}', name: 'my_app_productos_show', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Ver un producto de la tienda por id',
    )]
    #[OA\Parameter(
        name: "borrador",
        in: "query",
        description: "Muestra un producto en borrador con 0 o 1."
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function my_show(Productos $producto = null, Request $request, ProductosRepository $productosRepository, EntityManagerInterface $entityManager, UrlGeneratorInterface $router): Response
    {
        
        if (!$producto) {
            return $this->errorsInterface->error_message('No se ha proporcionado un parametro', Response::HTTP_BAD_REQUEST);
        }

        $allowedParams = [
            'borrador'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $borrador = filter_var($request->query->get('borrador', 0), FILTER_VALIDATE_BOOLEAN);

        $user = $this->getUser();
        $login = $entityManager->getRepository(Login::class)->find($user);
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $login]);

        $order_precio = $request->query->get('order_precio');
        $order_fecha = $request->query->get('order_fecha');

        $orderBy = ['precio_normal_producto' => 'ASC'];

        if ($order_precio === 'desc') {
            $orderBy = ['precio_normal_producto' => 'DESC'];
        }

        $orderBy = ['fecha_registro_producto' => 'ASC'];

        if ($order_fecha === 'desc') {
            $orderBy = ['fecha_registro_producto' => 'DESC'];
        }

        $criteria = ['tienda' => $tienda, 'id' => $producto, 'productos_tipo' => [1, 2, null],  'borrador' => $borrador];


        $productos = $productosRepository->findBy($criteria, $orderBy, 1);

        if (!$productos) {
            return $this->json(['message' => 'No se ha encontrado un producto'])->setStatusCode(400);
        }

        $productosArray = [];
        foreach ($productos as $producto) {

            if (!$producto->getProductosTipo() || ($producto->getProductosTipo()->getId() != 4 && $producto->getProductosTipo()->getId() != 3)) {

                $productosArray = $this->productoInterface->vista_privada($producto);
            }

        }

        return $this->json($productosArray);
    }



    #[Route('/api/productos/my_products', name: 'my_app_productos', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos por vendedor',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, ProductosRepository $productosRepository, EntityManagerInterface $entityManager, UrlGeneratorInterface $router, DetallePedidoRepository $detallePedidoRepository): Response
    {

        $user = $this->getUser();
        $login = $entityManager->getRepository(Login::class)->find($user);
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $login]);

        $productos = $productosRepository->findBy(['tienda' => $tienda, 'borrador' => false], ['fecha_registro_producto' => 'DESC']);

        //$productos = $productosRepository->findBy(['tienda' => $tienda], ['fecha_registro_producto' => 'DESC']);

        $productosArray = [];
        foreach ($productos as $producto) {

            if (!$producto->getProductosTipo() || ($producto->getProductosTipo()->getId() != 4 && $producto->getProductosTipo()->getId() != 3)) {

                $productosArray[] = $this->productoInterface->lista_privada($producto);

            }
        }

        return $this->json($productosArray);
    }



    #[Route('/api/productos/new', name: 'app_productos_new', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Añadir un producto a la tienda',
        content: new Model(type: ProductosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Login) {
            return $this->errorsInterface->error_message('No estás logueado.', Response::HTTP_UNAUTHORIZED);
        }

        $max_category = 0;
        $verificacion = strtoupper($user->getUsuarios()->getEstados()->getNobreEstado() ?? '');
        $tienda_verificacion = strtoupper($user->getTiendas()->getEstado()->getNobreEstado() ?? '');

        if (empty($verificacion) || empty($tienda_verificacion)) {
            return $this->errorsInterface->error_message('Error: Estado del usuario o de la tienda no disponible.', Response::HTTP_BAD_REQUEST);
        }

        // Permitir si la tienda o el usuario están verificados (o ambos)
        if ($verificacion !== "VERIFICADO" && $tienda_verificacion !== "VERIFICADO") {
            return $this->errorsInterface->error_message('Debes verificar tu identidad o tu tienda para publicar productos.', Response::HTTP_FORBIDDEN);
        }

        $usuario = $entityManager->getRepository(Usuarios::class)->findOneBy(['login' => $user]);

        if (!$usuario) {
            return $this->errorsInterface->error_message('El usuario no existe.', Response::HTTP_BAD_REQUEST, 'description', 'El usuario no existe en la base de datos.');
        }

        $nombre = $usuario->getNombre();
        $email = $usuario->getEmail();
        $documento = $usuario->getTipoDocumento();
        $telefono = $usuario->getCelular();
        $dni = $usuario->getDni();

        // Verificar cada campo individualmente

        $camposFaltantes = [];

        // Verificar cada campo individualmente y agregar el nombre del campo a $camposFaltantes si está vacío
        if (!$nombre) {
            $camposFaltantes[] = 'nombre';
        }
        if (!$email) {
            $camposFaltantes[] = 'email';
        }
        if (!$documento) {
            $camposFaltantes[] = 'documento';
        }
        if (!$telefono) {
            $camposFaltantes[] = 'teléfono';
        }
        if (!$dni) {
            $camposFaltantes[] = 'DNI';
        }

        // Verificar si hay campos faltantes
        if (!empty($camposFaltantes)) {
            $mensajeError = 'Completa la informacion de tu perfil para publicar un producto.';
            $error[] = [
                'description' => 'Los siguientes campos están vacíos: ' . implode(', ', $camposFaltantes),
            ];

            return $this->errorsInterface->error_message(
                $mensajeError,
                417, // O Response::HTTP_EXPECTATION_FAILED si está usando constantes
                null,
                $error
            );
        }


        if ($tienda_verificacion === 'VERIFICADO') {

            $max_category = 5;
        } else {
            $max_category = 4;
        }

        $producto_tipo = $entityManager->getRepository(ProductosTipo::class)->findOneBy(['id' => 1]);


        $f_form = filter_var($request->query->get('from_form'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('from_form'), FILTER_VALIDATE_BOOLEAN) : null;



        $login = $entityManager->getRepository(Login::class)->find($user);
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $login]);

        $producto = new Productos();
        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $categorias = $form['categorias']->getData();


            if (count($categorias) > 0 & count($categorias) <= $max_category) {

                $nombre = $form->get('nombre_producto')->getData();
                $cantidad = $form->get('cantidad_producto')->getData();


                $slug = strtolower(str_replace(' ', '-', $nombre));

                // Convierte caracteres acentuados y especiales a su equivalente ASCII
                $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

                // Elimina cualquier carácter que no sea una letra, número o guión
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

                // Limitar el slug a una longitud máxima (opcional)
                $slug = substr($slug, 0, 100);

                // Añadir un identificador único para evitar duplicados
                $slug = $slug . '-' . uniqid();

                $descuento = $form->get('descuento')->getData();
                $precio_normal = $form->get('precio_normal_producto')->getData();


                if ($descuento !== null && $descuento > 0) {
                    $precioRebajadoProducto = ($descuento * $precio_normal) / 100;
                    $total = $precio_normal - $precioRebajadoProducto;
                    $producto->setPrecioRebajadoProducto(round($total, 2, PHP_ROUND_HALF_UP));
                    $producto->setTieneDescuento(true);

                } elseif ($descuento == null || $descuento == 0) {

                    $producto->setPrecioRebajadoProducto(null);
                    $producto->setTieneDescuento(false);
                }

                if ($cantidad == null || $cantidad == '') {
                    $producto->setCantidadProducto(0);
                }

                $producto->setSlugProducto($slug);
                $producto->setTienda($tienda);
                $producto->setFromForm($f_form);
                $producto->setProductosTipo($producto_tipo);
                $entityManager->persist($producto);
                $entityManager->flush();


                return $this->errorsInterface->succes_message('Guardado', 'id', $producto->getId());

            } else {

                return $this->errorsInterface->error_message('Debes seleccionar 1 categoría o máximo ' . $max_category, Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->errorsInterface->form_errors($form);
    }


    #[Route('/api/productos/edit/{id}', name: 'app_productos_edit', methods: ['PATCH', 'PUT'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Editar un producto por id',
        content: new Model(type: ProductosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(?Productos $producto, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no encontrado.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        if ($tienda !== $producto->getTienda()) {

            return $this->errorsInterface->error_message('No se puede actualizar este producto,no pertenece a tu tienda', Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(ProductosType::class, $producto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $nombre = $form->get('nombre_producto')->getData();



            $descuento = $form->get('descuento')->getData();
            $precio_normal = $form->get('precio_normal_producto')->getData();


            if ($descuento !== null && $descuento > 0) {
                $precioRebajadoProducto = ($descuento * $precio_normal) / 100;
                $total = $precio_normal - $precioRebajadoProducto;
                $producto->setPrecioRebajadoProducto(round($total, 2, PHP_ROUND_HALF_UP));
                $producto->setTieneDescuento(true);

            } elseif ($descuento == null || $descuento == 0) {

                $producto->setPrecioRebajadoProducto(null);
                $producto->setTieneDescuento(false);
            }

            $producto->setFechaEdicion(new DateTime());

            if ($nombre !== $producto->getNombreProducto()) {

                $slug = strtolower(str_replace(' ', '-', $nombre));

                // Convierte caracteres acentuados y especiales a su equivalente ASCII
                $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

                // Elimina cualquier carácter que no sea una letra, número o guión
                $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

                // Limitar el slug a una longitud máxima (opcional)
                $slug = substr($slug, 0, 100);

                // Añadir un identificador único para evitar duplicados
                $slug = $slug . '-' . uniqid();

                $producto->setSlugProducto($slug);
            }


            $entityManager->flush();

            return $this->errorsInterface->succes_message('Editado', 'id_producto', $producto->getId());
        }

        return $this->errorsInterface->form_errors($form);
    }


    #[Route('/api/productos/galeria/{id}', name: 'app_productos_galeria', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Añade una o más imágenes al producto',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'image[]', // Nota: aquí debe especificarse con los corchetes
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Lista de archivos de imágenes'
                        ),
                    ]
                )
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function galery(?Productos $producto, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$producto) {
            return $this->errorsInterface->error_message('No hay producto.', Response::HTTP_BAD_REQUEST);
        }
        $form = $this->createForm(ProductosGaleriaType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slug = str_replace(' ', '-', $producto->getNombreProducto());


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $archivos = $form['image']->getData();

            if (count($archivos) > 0 & count($archivos) <= 6 & $archivos != null) {

                foreach ($archivos as $archivo) {

                    try {

                        if ($archivo instanceof UploadedFile) {
                            // Genera un nombre único para el archivo
                            $nombreArchivo = $slug . '-' . uniqid() . '.' . $archivo->guessExtension();

                            // Mueve el archivo al directorio de almacenamiento
                            $directorioAlmacenamiento = $this->getParameter('images_directory');

                            $archivo->move($directorioAlmacenamiento, $nombreArchivo);

                            $galeria = new ProductosGaleria();
                            $galeria->setUrlProductoGaleria($nombreArchivo);
                            $galeria->setProducto($producto);
                            $entityManager->persist($galeria);
                            $entityManager->flush();

                        }

                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                return $this->errorsInterface->succes_message('Guardado');
            } else {

                return $this->errorsInterface->error_message('Debes cargar una imagen o maximo 6', Response::HTTP_BAD_REQUEST);

            }
        }

        return $this->errorsInterface->form_errors($form);

    }

    #[Route('/api/productos/galeria/delete/{id}', name: 'eliminar_galeria_producto_base', methods: ['DELETE'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Eliminar una imagen de galería por id', // Descripción actualizada
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_galeria(?ProductosGaleria $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Login) {
            return $this->errorsInterface->error_message('No esta autenticado', Response::HTTP_UNAUTHORIZED);
        }

        if (!$id) {
            // Si $id es null, significa que la galeria con el ID proporcionado no fue encontrada
            return $this->errorsInterface->error_message('Imagen de galería no encontrada.', Response::HTTP_NOT_FOUND); // Usar 404 es más apropiado aquí
        }

        $producto = $id->getProducto();

        // Esta verificación es un poco redundante si la relación es ManyToOne NOT NULL
        // y el ParamConverter funciona correctamente, pero no hace daño.
        if (!$producto) {
            return $this->errorsInterface->error_message('Error al obtener el producto asociado a la imagen.', Response::HTTP_CONFLICT);
        }

        // Verificación de propiedad usando la tienda del producto asociado a la imagen
        if ($user->getTiendas() !== $producto->getTienda()) {
            return $this->errorsInterface->error_message('La imagen del producto no pertenece a tu tienda.', Response::HTTP_FORBIDDEN); // Usar 403 Forbidden es más apropiado
        }

        // Obtener todas las galerías asociadas al producto
        $galeriasDelProducto = $producto->getProductosGalerias();

        // Verificar si la imagen que se intenta eliminar es la última
        if ($galeriasDelProducto->count() <= 1) {
            return $this->errorsInterface->error_message('No se puede eliminar la última imagen del producto.', Response::HTTP_BAD_REQUEST);
        }

        // Si no es la última imagen, proceder con la eliminación
        $entityManager->remove($id);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Imagen de galería eliminada correctamente.'); // Mensaje de éxito más específico
    }

    #[Route('/api/productos/galeria/edit/{id}', name: 'editar_galeria_producto_base', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Edita una imagen del producto base.',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'image',
                            type: 'file',
                            description: 'Imagen de un producto'
                        ),
                    ]
                )
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit_galeria(?ProductosGaleria $productosGaleria, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$productosGaleria) {
            return $this->errorsInterface->error_message('No se encontro el dato', Response::HTTP_BAD_REQUEST);
        }
        $form = $this->createForm(EditProductosGaleriaType::class, $productosGaleria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slug = str_replace(' ', '-', $productosGaleria->getProducto()->getNombreProducto());


            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);
            $archivos = $form->get('image')->getData();


            // Mueve el archivo al directorio de almacenamiento
            if ($archivos instanceof UploadedFile) {
                // Genera un nombre único para el archivo
                $nombreArchivo = $slug . '-' . uniqid() . '.' . $archivos->guessExtension();

                // Mueve el archivo al directorio de almacenamiento
                $directorioAlmacenamiento = $this->getParameter('images_directory');
                $archivos->move($directorioAlmacenamiento, $nombreArchivo);
                $productosGaleria->setUrlProductoGaleria($nombreArchivo);
                $entityManager->flush();

            }

            return $this->errorsInterface->succes_message('Guardado', 'id_imagen_producto', $productosGaleria->getId());

        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/productos/variaciones/{id}', name: 'add_variaciones_producto', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Añadir una variante al producto por id',
        content: new Model(type: VariacionesType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function variations_add(?Productos $producto, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($producto === null) {
            return $this->errorsInterface->error_message('Producto no proporcionado', Response::HTTP_NOT_FOUND);
        }

        $variacion = new Variaciones();
        $form = $this->createForm(VariacionesType::class, $variacion);
        $form->handleRequest($request);

        try {
            $entityManager->beginTransaction();

            if ($form->isSubmitted() && $form->isValid()) {
                $cantidad = $form->get('cantidad')->getData();
                $descripcion = $form->get('descripcion')->getData();

                // Utilizar el precio normal del producto si no se proporciona en el formulario
                $descripcion_base = $producto->getDescripcionCortaProducto();

                $precio_normal = $form->get('precio')->getData();
                $precio_normal = ($precio_normal !== null) ? $precio_normal : $producto->getPrecioNormalProducto();

                $cantidad = ($cantidad !== null && $cantidad !== "")
                    ? $cantidad
                    : $producto->getCantidadProducto() ?? 0;

                // Descuento se establece a 0 por defecto
                $descuento = $form->get('descuento')->getData() ?? 0;
                // Calcular el precio rebajado
                $precio_rebajado = ($descuento !== null) ? ($precio_normal - ($descuento * $precio_normal) / 100) : null;

                $variacion->setPrecio($precio_normal);
                $variacion->setPrecioRebajado(round($precio_rebajado, 2, PHP_ROUND_HALF_UP));

                $variacion->setCantidad($cantidad); // <-- Asignar la cantidad validada
                $variacion->setDescripcion($descripcion ?: $descripcion_base);

                $variacion->setProductos($producto);
                $entityManager->persist($variacion);
                $entityManager->flush();
                $entityManager->commit();

                return $this->errorsInterface->succes_message('Guardado', 'id', $variacion->getId());
            }
            // Manejo de errores

            $entityManager->rollback();

            return $this->errorsInterface->form_errors($form);

        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }



    #[Route('/api/productos/variaciones/edit/{id}', name: 'editar_variaciones', methods: ['PATCH'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Editar una variante de un producto',
        content: new Model(type: VariacionesType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit_variations(Request $request, ?Variaciones $variacion, EntityManagerInterface $entityManager): Response
    {
        if ($variacion === null) {
            return $this->errorsInterface->error_message('Variante no encontrada.', Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(VariacionesType::class, $variacion);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $descuento = $form->get('descuento')->getData();
            $precio_normal = $variacion->getPrecio();

            if ($descuento !== null && is_numeric($descuento) && $descuento >= 0 && $descuento <= 100) {
                $precioRebajadoProducto = ($descuento * $precio_normal) / 100;
                $total = $precio_normal - $precioRebajadoProducto;
                $variacion->setPrecioRebajado(round($total, 2, PHP_ROUND_HALF_UP));
            }

            $entityManager->flush();

            return $this->errorsInterface->succes_message('Editado', 'id_variacion', $variacion->getId());

        }

        return $this->errorsInterface->form_errors($form);
    }


    #[Route('/api/productos/variaciones_galeria/{id}', name: 'app_productos_variaciones_galeria', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Añade una o más imágenes al producto',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'image[]', // Nota: aquí debe especificarse con los corchetes
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Lista de archivos de imágenes'
                        ),
                    ]
                )
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function galery_variations(?Variaciones $variacion, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$variacion) {
            return $this->errorsInterface->error_message('Variacion no encontrada.', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(VariacionGaleriaType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slug = str_replace(' ', '-', $variacion->getProductos()->getNombreProducto());

            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $archivos = $form['image']->getData();


            if (count($archivos) > 0 & count($archivos) <= 5) {

                foreach ($archivos as $archivo) {

                    try {

                        if ($archivo instanceof UploadedFile) {
                            // Genera un nombre único para el archivo
                            $nombreArchivo = $slug . '-' . uniqid() . '.' . $archivo->guessExtension();

                            // Mueve el archivo al directorio de almacenamiento
                            $directorioAlmacenamiento = $this->getParameter('images_directory');
                            $archivo->move($directorioAlmacenamiento, $nombreArchivo);

                            $galeria = new VariacionesGaleria();
                            $galeria->setUrlVariacion($nombreArchivo);
                            $galeria->setVariaciones($variacion);
                            $entityManager->persist($galeria);
                            $entityManager->flush();

                        }

                    } catch (Exception $e) {
                        return $this->errorsInterface->error_message($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                }

                return $this->errorsInterface->succes_message('Guardado');

            } else {
                return $this->errorsInterface->error_message('Debes cargar una imagen o maximo 5', Response::HTTP_BAD_REQUEST);

            }


        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/productos/variaciones_galeria_edit/{id}', name: 'app_productos_variaciones_galeria_edit', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Editar una imagen de variacion de producto',
        content: [
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: VariacionGaleriaEditType::class))
            ),
        ]
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function galery_variations_edit(?VariacionesGaleria $variacionesGaleria, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$variacionesGaleria) {
            return $this->errorsInterface->error_message('Paramentro no encontrado.', Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(VariacionGaleriaEditType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slug = str_replace(' ', '-', $variacionesGaleria->getVariaciones()->getProductos()->getNombreProducto());

            // Convierte el slug a minúsculas.
            $slug = strtolower($slug);

            // Elimina caracteres especiales y acentos.
            $slug = preg_replace('/[^a-z0-9áéíóúüñ-]/', '', $slug);

            // Elimina guiones duplicados.
            $slug = preg_replace('/-+/', '-', $slug);

            $archivo = $form['image']->getData();


            // Mueve el archivo al directorio de almacenamiento
            if ($archivo instanceof UploadedFile) {
                // Genera un nombre único para el archivo
                $nombreArchivo = $slug . '-' . uniqid() . '.' . $archivo->guessExtension();

                // Mueve el archivo al directorio de almacenamiento
                $directorioAlmacenamiento = $this->getParameter('images_directory');
                $archivo->move($directorioAlmacenamiento, $nombreArchivo);

                $variacionesGaleria->setUrlVariacion($nombreArchivo);
                $entityManager->flush();

            }

            return $this->errorsInterface->succes_message('Guardado', 'id_imagen_variacion', $variacionesGaleria->getId());

        }

        return $this->errorsInterface->form_errors($form);

    }


    #[Route('/api/productos/delete_variacion/{id}', name: 'borrar_variacion', methods: ['DELETE'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Eliminar un variacion de un producto',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_variacion(?Variaciones $variacion, EntityManagerInterface $entityManager): Response
    {
        $pedidos = $entityManager->getRepository(DetallePedido::class)->findBy(['IdVariacion' => $variacion]);

        if (!empty($pedidos)) {
            return $this->json(['message' => 'No se puede eliminar la variante  porque tiene pedidos asociados'])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($variacion);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Eliminado');

    }


    #[Route('/api/variaciones_galeria/delete/{id}', name: 'eliminar_variacion_galeria', methods: ['DELETE'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Eliminar una imagen de una variacion de un producto',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete_variacion_galeria(?VariacionesGaleria $variacionesGaleria, EntityManagerInterface $entityManager): Response
    {
        if (!$variacionesGaleria) {
            return $this->errorsInterface->error_message('Parametro no encontrado.', Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($variacionesGaleria);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Eliminado');
    }

    #[Route('/api/productos/lista_variaciones/{id}', name: 'list_variations', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Listar variaciones por el id de un producto',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list_variations(Request $request, Productos $producto, VariacionesRepository $variacionesRepository, UrlGeneratorInterface $router): Response
    {
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();
        $variaciones = $variacionesRepository->findBy(['productos' => $producto]);
        $variacionesArray = [];

        foreach ($variaciones as $variacion) {
            $terminosArray = [];
            foreach ($variacion->getTerminos() as $termino) {
                $terminosArray[] = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo()
                ];
            }

            $galeriaArray = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $galeriaArray[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $domain . $host . '/public/productos/' . $galeria->getUrlVariacion()
                ];
            }

            $variacionesArray[] = [
                'id' => $variacion->getId(),
                'descripcion' => $variacion->getDescripcion(),
                'precio' => $variacion->getPrecio(),
                'precio_rebajado' => $variacion->getPrecioRebajado(),
                'cantidad' => $variacion->getCantidad(),
                'sku' => $variacion->getSku(),
                'terminos' => $terminosArray,
                'variacionesGalerias' => $galeriaArray
            ];
        }

        return $this->json($variacionesArray);
    }


    #[Route('/api/productos/show_variaciones/{id}', name: 'show_variations', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Mirar una variacion de un producto por id',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show_variations(Variaciones $variacion, VariacionesRepository $variacionesRepository): Response
    {
        $variaciones = $variacionesRepository->findBy(['productos' => $variacion], null, 1);
        $variacionesArray = [];

        foreach ($variaciones as $variacion) {
            $terminosArray = [];
            foreach ($variacion->getTerminos() as $termino) {
                $terminosArray[] = [
                    'id' => $termino->getId(),
                    'nombre' => $termino->getNombre(),
                    'codigo' => $termino->getCodigo()
                ];
            }

            $galeriaArray = [];

            foreach ($variacion->getVariacionesGalerias() as $galeria) {
                $galeriaArray[] = [
                    'id' => $galeria->getId(),
                    'url_variacion' => $galeria->getUrlVariacion()
                ];
            }

            $variacionesArray = [
                'id' => $variacion->getId(),
                'descripcion' => $variacion->getDescripcion(),
                'precio' => $variacion->getPrecio(),
                'precio_rebajado' => $variacion->getPrecioRebajado(),
                'cantidad' => $variacion->getCantidad(),
                'sku' => $variacion->getSku(),
                'terminos' => $terminosArray,
                'variacionesGalerias' => $galeriaArray
            ];
        }

        return $this->json($variacionesArray);
    }



    #[Route('/api/productos/favoritos/{id}', name: 'productos_favoritos', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Añadir o quitar un producto de la lista de favoritos por su id',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function favoritos(EntityManagerInterface $entityManager, Productos $producto): Response
    {
        $user = $this->getUser();
        $favoritoRepo = $entityManager->getRepository(ProductosFavoritos::class);
        $favorito = $favoritoRepo->findOneBy(['login' => $user, 'producto' => $producto]);

        if (!$favorito) {

            $favorito = new ProductosFavoritos();
            $favorito->setLogin($user);
            $favorito->setProducto($producto);
            $entityManager->persist($favorito);
            $message = 'Guardado';
        } else {

            $entityManager->remove($favorito);
            $message = 'Eliminado';
        }

        $entityManager->flush();

        return $this->errorsInterface->succes_message($message);

    }

    #[Route('/api/productos/favorite_list', name: 'list_favoritos', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos favoritos',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function mi_favorito(Request $request, ProductosFavoritosRepository $productosFavoritosRepository, UrlGeneratorInterface $router): Response
    {
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();
        $user = $this->getUser();


        $productos = $productosFavoritosRepository->findBy(['login' => $user], ['fecha_favorita' => 'DESC']);

        $productosArray = [];
        foreach ($productos as $producto) {
            $precioNormalProducto = $producto->getProducto()->getPrecioNormalProducto();
            $precioRebajadoProducto = $producto->getProducto()->getPrecioRebajadoProducto();
            $marca_id = $producto->getProducto()->getMarcas() ? $producto->getProducto()->getMarcas()->getId() : null;
            $marcas = $producto->getProducto()->getMarcas() ? $producto->getProducto()->getMarcas()->getNombreM() : null;

            // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
            if ($precioNormalProducto <= 0) {
                $porcentajeDescuento = 0;
            } elseif ($precioRebajadoProducto != null) {
                // Calcula el porcentaje de descuento.
                $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
            } else {
                $porcentajeDescuento = null;
            }
            $imagenesArray = [];

            foreach ($producto->getProducto()->getProductosGalerias() as $galeria) {
                $imagenesArray[] = [
                    'id' => $galeria->getId(),
                    'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

                ];
            }

            $subcategoriasArray = [];

            foreach ($producto->getProducto()->getSubcategorias() as $subcategoria) {
                $subcategoriasArray[] = [
                    'id_categoria' => $subcategoria->getCategorias()->getId(),
                    'categoria' => $subcategoria->getCategorias()->getNombre(),
                    'id' => $subcategoria->getId(),
                    'subcategoria' => $subcategoria->getNombre()
                ];
            }


            $categoriasArray = [];

            foreach ($producto->getProducto()->getCategorias() as $categoria) {
                $categoriasArray[] = [
                    'id' => $categoria->getId(),
                    'nombre' => $categoria->getNombre(),
                    'slug' => $categoria->getSlug()
                ];

            }


            $total = 0;
            $count = 0;
            $promedio = 0;

            foreach ($producto->getProducto()->getProductosComentarios() as $comentario) {

                $calificacion = $comentario->getCalificacion();
                if ($calificacion !== null && $calificacion !== '') {
                    $total += $calificacion;
                    $count++;
                }

                $promedio = $count > 0 ? $total / $count : null;


            }


            $productosArray[] = [
                'id' => $producto->getProducto()->getId(),
                'nombre_producto' => $producto->getProducto()->getNombreProducto(),
                'slug_producto' => $producto->getProducto()->getSlugProducto(),
                'precio_normal_producto' => $precioNormalProducto,
                'precio_rebajado_producto' => $precioRebajadoProducto,
                'galeria' => $imagenesArray,
                'variable' => $producto->getProducto()->isVariable(),
                'calificacion' => $promedio,
            ];
        }

        return $this->json($productosArray);

    }



    #[Route('/productos/public_favorite_list/{username}', name: 'list_favoritos_publica', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos favoritos publica',
    )]
    public function favoritos_publicos(Request $request, EntityManagerInterface $em, ProductosFavoritosRepository $productosFavoritosRepository, UrlGeneratorInterface $router, $username = null): Response
    {
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();

        if (!$username) {
            return $this->errorsInterface->error_message('Usuario no proporcionado', Response::HTTP_NOT_FOUND);
        }

        $user = $em->getRepository(Login::class)->findOneBy(['username' => $username]);


        if (!$user) {
            return $this->errorsInterface->error_message('Usuario no encontrado', Response::HTTP_NOT_FOUND);
        }


        $user_data = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'nombre' => $user->getUsuarios() ? $user->getUsuarios()->getNombre() : '',
            'apellido' => $user->getUsuarios() ? $user->getUsuarios()->getApellido() : '',
            'avatar' => $user->getUsuarios() ? $domain . $host . '/public/user/selfie/' . $user->getUsuarios()->getAvatar() : ''
        ];


        $productos = $productosFavoritosRepository->findBy(['login' => $user], ['fecha_favorita' => 'DESC']);

        $productosArray = [];
        foreach ($productos as $producto) {
            $precioNormalProducto = $producto->getProducto()->getPrecioNormalProducto();
            $precioRebajadoProducto = $producto->getProducto()->getPrecioRebajadoProducto();
            $marca_id = $producto->getProducto()->getMarcas() ? $producto->getProducto()->getMarcas()->getId() : null;
            $marcas = $producto->getProducto()->getMarcas() ? $producto->getProducto()->getMarcas()->getNombreM() : null;

            // Asegúrate de manejar casos en los que el precio normal sea cero o nulo para evitar errores de división.
            if ($precioNormalProducto <= 0) {
                $porcentajeDescuento = 0;
            } elseif ($precioRebajadoProducto != null) {
                // Calcula el porcentaje de descuento.
                $porcentajeDescuento = (($precioNormalProducto - $precioRebajadoProducto) / $precioNormalProducto) * 100;
            } else {
                $porcentajeDescuento = null;
            }
            $imagenesArray = [];

            foreach ($producto->getProducto()->getProductosGalerias() as $galeria) {
                $imagenesArray[] = [
                    'id' => $galeria->getId(),
                    'url_producto_galeria' => $domain . $host . '/public/productos/' . $galeria->getUrlProductoGaleria(),

                ];
            }





            $total = 0;
            $count = 0;
            $promedio = 0;

            foreach ($producto->getProducto()->getProductosComentarios() as $comentario) {

                $calificacion = $comentario->getCalificacion();
                if ($calificacion !== null && $calificacion !== '') {
                    $total += $calificacion;
                    $count++;
                }

                $promedio = $count > 0 ? $total / $count : null;


            }


            $productosArray[] = [
                'id' => $producto->getProducto()->getId(),
                'nombre_producto' => $producto->getProducto()->getNombreProducto(),
                'slug_producto' => $producto->getProducto()->getSlugProducto(),
                'precio_normal_producto' => $precioNormalProducto,
                'precio_rebajado_producto' => $precioRebajadoProducto,
                'galeria' => $imagenesArray,
                'variable' => $producto->getProducto()->isVariable(),
                'calificacion' => $promedio,
            ];
        }

        return $this->json(['message' => 'Lista retornada', 'data' => ['usuario' => $user_data, 'favoritos' => $productosArray]]);
    }




    #[Route('/api/productos/comentarios/{id}/{pedido}', name: 'producto_comentarios', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Agregar comentario a un producto de un pedido con el id producto y un pedido',
        content: new Model(type: ProductosComentariosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function comentarios_productos(Request $request, EntityManagerInterface $entityManager, $pedido = null, $id = null): Response
    {

        $user = $this->getUser();
        $pedido = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido]);
        $producto = $entityManager->getRepository(Productos::class)->find($id);
        if (!$pedido) {
            return $this->errorsInterface->error_message('El pedido no existe.', Response::HTTP_NOT_FOUND, null, null);
        }

        if (!$producto) {
            return $this->errorsInterface->error_message('El producto no existe.', Response::HTTP_NOT_FOUND, null, null);
        }
        $comentarios = $entityManager->getRepository(ProductosComentarios::class)->findBy(['login' => $user, 'pedido' => $pedido, 'productos' => $producto]);


        if (!empty($comentarios)) {

            return $this->errorsInterface->error_message('Ya has hecho un comentario en el producto de este pedido', Response::HTTP_BAD_REQUEST);
        }

        if ($pedido->getEstado() !== 'APPROVED') {
            return $this->errorsInterface->error_message('El pedido debe estar aprobado para comentar.', Response::HTTP_BAD_REQUEST);
        }


        if (
            ($pedido->getEstadoEnvio()->getId() !== 22 && $pedido->getTipoEnvio() == 'A DOMICILIO') || ($pedido->getEstadoRetiro()->getId() !== 22 && $pedido->getTipoEnvio() == 'RETIRO EN TIENDA FISICA')
            || ($pedido->getEstadoEnvio()->getId() !== 22 && $pedido->getEstadoRetiro()->getId() !== 22 && $pedido->getTipoEnvio() == 'AMBOS')
        ) {
            return $this->errorsInterface->error_message('El pedido debe marcarse como entregado para realizar un comentario', Response::HTTP_BAD_REQUEST);
        }

        $comentario = new ProductosComentarios();
        $form = $this->createForm(ProductosComentariosType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $coment = $form->get('comentario')->getData();
            $calificacion = $form->get('calificacion')->getData();

            if ($calificacion > 5) {
                $calificacion = 5;
            }
            $comentario->setComentario($coment ? $coment : '');
            $comentario->setCalificacion($calificacion);

            $comentario->setLogin($user);
            $comentario->setPedido($pedido);
            $comentario->setProductos($producto);
            $entityManager->persist($comentario);
            $entityManager->flush();

            return $this->errorsInterface->succes_message('Guardado');
        }

        return $this->errorsInterface->form_errors($form);

    }

    #[Route('/api/productos/comentario_pedido/{id}/{pedido}', name: 'comentarios_usuario', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Listar de comentarios a los productos de un pedido de un cliente',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list_comentarios_usuario(ProductosComentariosRepository $productosComentariosRepository, EntityManagerInterface $entityManager, $pedido = null, Productos $producto = null): Response
    {
        $user = $this->getUser();
        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no existe', Response::HTTP_NOT_FOUND);
        }
        $pedido = $entityManager->getRepository(Pedidos::class)->findOneBy(['numero_pedido' => $pedido]);
        if (!$pedido) {
            return $this->errorsInterface->error_message('Pedido no existe', Response::HTTP_NOT_FOUND);
        }
        $comentarios = $productosComentariosRepository->findBy(['productos' => $producto, 'login' => $user, 'pedido' => $pedido]);
        $comentariosArrray = null;

        foreach ($comentarios as $comentario) {

            $comentariosArrray = [
                'id' => $comentario->getId(),
                'comentario' => $comentario->getComentario() ? $comentario->getComentario() : '',
                'calificacion' => $comentario->getCalificacion() ? $comentario->getCalificacion() : '',
                'fecha_comentario' => $comentario->getFecha(),
            ];
        }

        return $this->json(['data' => $comentariosArrray]);
    }


    #[Route('/productos/comentarios/{id}', name: 'productos_comentarios', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Lista de comentarios de un producto.',
    )]
    public function lista_comentarios(Productos $producto, ProductosComentariosRepository $productosComentariosRepository): Response
    {

        $comentarios = $productosComentariosRepository->findBy(['productos' => $producto]);
        $total_reviews = $productosComentariosRepository->count(['productos' => $producto]);
        $comentariosArrray = [];
        $total = 0;
        $count = 0;

        foreach ($comentarios as $comentario) {
            $comentariosArrray[] = [
                'username' => $comentario->getLogin()->getUsername(),
                'comentario' => $comentario->getComentario() ? $comentario->getComentario() : '',
                'calificacion' => $comentario->getCalificacion() ? $comentario->getCalificacion() : '',
                'fecha_comentario' => $comentario->getFecha(),
            ];

            $calificacion = $comentario->getCalificacion();
            if ($calificacion !== null && $calificacion !== '') {
                $total += $calificacion;
                $count++;
            }
        }

        $promedio = $count > 0 ? $total / $count : null;

        return $this->json(['comentarios' => $comentariosArrray, 'calificacion_producto' => $promedio, 'total_reviews' => $total_reviews]);
    }


    #[Route('/tienda/comentarios/{username}', name: 'tienda_comentarios', methods: ['GET'])]
    #[OA\Tag(name: 'Tienda')]
    #[OA\Response(
        response: 200,
        description: 'Lista de productos con comentarios',
    )]
    public function comentarios_tienda(UrlGeneratorInterface $router, Request $request, $username, EntityManagerInterface $entityManager, ProductosComentariosRepository $productosComentariosRepository): Response
    {
        $domain = $request->getSchemeAndHttpHost();
        $host = $router->getContext()->getBaseUrl();

        $user = $entityManager->getRepository(Login::class)->findOneBy(['username' => $username]);

        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $comentarios = $productosComentariosRepository->comentarios_tienda($tienda);
        $comentariosArrray = [];

        foreach ($comentarios as $comentario) {

            $imagenesArray = [];

            foreach ($comentario->getProductos()->getProductosGalerias() as $imagen) {
                $imagenesArray[] = [
                    'imagen' => $imagen->getUrlProductoGaleria() ? $domain . $host . '/public/productos/' . $imagen->getUrlProductoGaleria() : '',
                ];
            }

            $comentariosArrray[] = [
                'username' => $comentario->getLogin()->getUsername(),
                'producto' => $comentario->getProductos()->getNombreProducto(),
                'slug' => $comentario->getProductos()->getSlugProducto(),
                'comentario' => $comentario->getComentario() ? $comentario->getComentario() : '',
                'calificacion' => $comentario->getCalificacion() ? $comentario->getCalificacion() : '',
                'fecha_comentario' => $comentario->getFecha(),
                'galeria' => $imagenesArray
            ];

        }
        return $this->json($comentariosArrray);
    }


    #[Route('/productos/categorias', name: 'categorias_index', methods: ['GET'])]
    #[OA\Tag(name: 'Categorias')]
    #[OA\Response(
        response: 200,
        description: 'Lista de categorias ',
    )]
    #[OA\Parameter(
        name: "filtrar_destacados",
        in: "query",
        description: "Filtra las categorias si estas estan publicadas y son destacadas(boolean)"
    )]
    #[OA\Parameter(
        name: "with_products",
        in: "query",
        description: "Filtro de categorias que tengan productos"
    )]
    public function productos_marcas_list(Request $request, CategoriasRepository $categoriasRepository, UrlGeneratorInterface $router): Response
    {
        $allowedParams = [
            'filtrar_destacados',
            'with_products'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }
        $filtrar_destacados = filter_var($request->query->get('filtrar_destacados'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('filtrar_destacados'), FILTER_VALIDATE_BOOLEAN) : null;
        $with_products = filter_var($request->query->get('with_products'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('with_products'), FILTER_VALIDATE_BOOLEAN) : null;
        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();


        if ($filtrar_destacados == null || $filtrar_destacados == false) {
            $categorias = $categoriasRepository->findBy(['publicado' => true], ['nombre' => 'ASC']);
        } elseif ($filtrar_destacados == true) {
            $categorias = $categoriasRepository->findBy(['destacado' => true, 'publicado' => true], ['nombre' => 'ASC']);
        }

        if ($with_products == true) {
            $categorias = $categoriasRepository->findCategoriesWithProducts();
        }


        $categoriasArray = [];
        foreach ($categorias as $categoria) {
            $subcategoriasArray = [];

            foreach ($categoria->getSubcategorias() as $subcategoria) {
                $subcategoriasArray[] = [
                    'id' => $subcategoria->getId(),
                    'nombre' => $subcategoria->getNombre(),
                    'slug' => $subcategoria->getSlug()
                ];
            }

            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'description' => $categoria->getDescripcion(),
                'destacado' => $categoria->isDestacado(),
                'slug' => $categoria->getSlug(),
                'slider-image' => $categoria->getImg() ? $domain . $host . '/public/categorias/' . $categoria->getImg() : '',
                'banner-image' => $categoria->getBanner() ? $domain . $host . '/public/categorias/' . $categoria->getBanner() : '',
                'subcategorias' => $subcategoriasArray

            ];

        }

        return $this->json($categoriasArray);
    }


    #[Route('/ver/categoria/{slug}', name: 'show_categoria', requirements: ['slug' => '.+'], defaults: ['slug' => null], methods: ['GET'])]
    #[OA\Tag(name: 'Categorias')]
    #[OA\Response(
        response: 200,
        description: 'Ver una categoria por slug ',
    )]
    public function view_category($slug, CategoriasRepository $categoriasRepository, UrlGeneratorInterface $router, Request $request): Response
    {

        if ($slug === null) {
            return $this->errorsInterface->error_message('Parámetro slugger no proporcionado', Response::HTTP_BAD_REQUEST);
        }

        $categorias = $categoriasRepository->findBy(['slug' => $slug], null, 1);

        if (empty($categorias)) {
            return $this->errorsInterface->error_message('Categoria no encontrada', Response::HTTP_NOT_FOUND);
        }

        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();

        $categoriasArray = [];

        foreach ($categorias as $categoria) {

            $subcategoriasArray = [];

            foreach ($categoria->getSubcategorias() as $subcategoria) {
                $subcategoriasArray[] = [
                    'id' => $subcategoria->getId(),
                    'nombre' => $subcategoria->getNombre(),
                    'slug' => $subcategoria->getSlug(),
                    'image' => $domain . $host . '/public/subcategorias/' . $subcategoria->getImage()
                ];
            }

            $categoriasArray = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'description' => $categoria->getDescripcion(),
                'destacado' => $categoria->isDestacado(),
                'slug' => $categoria->getSlug(),
                'banner-image' => $categoria->getBanner() ? $domain . $host . '/public/categorias/' . $categoria->getBanner() : '',
                'slider-image' => $categoria->getImg() ? $domain . $host . '/public/categorias/' . $categoria->getImg() : '',
                'title' => $categoria->getTitle(),
                'subcategorias' => $subcategoriasArray

            ];
        }

        return $this->json($categoriasArray);
    }


    #[Route('/ver/subcategoria/{slug}', name: 'show_subcategoria', requirements: ['slug' => '.+'], defaults: ['slug' => null], methods: ['GET'])]
    #[OA\Tag(name: 'Categorias')]
    #[OA\Response(
        response: 200,
        description: 'Mirar una subcategoria por slug',
    )]
    public function view_subcategory($slug, SubcategoriasRepository $subcategoriasRepository, UrlGeneratorInterface $router, Request $request): Response
    {
        if ($slug === null) {
            return $this->errorsInterface->error_message('Parámetro slugger no proporcionado', Response::HTTP_BAD_REQUEST);
        }


        $subcategorias = $subcategoriasRepository->findBy(['slug' => $slug], null, 1);

        if (empty($subcategorias)) {
            return $this->errorsInterface->error_message('Subcategoria no encontrada', Response::HTTP_NOT_FOUND);
        }

        $host = $router->getContext()->getBaseUrl();
        $domain = $request->getSchemeAndHttpHost();
        $subcategoriasArray = [];

        foreach ($subcategorias as $subcategoria) {
            $subcategoriasArray = [
                'id' => $subcategoria->getId(),
                'nombre' => $subcategoria->getNombre(),
                'slug' => $subcategoria->getSlug(),
                'image' => $domain . $host . '/public/subcategorias/' . $subcategoria->getImage()
            ];
        }

        return $this->json($subcategoriasArray);

    }

    #[Route('/api/categorias/select', name: 'select_categorias', methods: ['GET'])]
    #[OA\Tag(name: 'Categorias')]
    #[OA\Response(
        response: 200,
        description: 'Lista de categorias disponibles para seleccionar al crear un producto',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list_c(CategoriasRepository $categoriasRepository, EntityManagerInterface $entityManager): Response
    {

        $categorias = $categoriasRepository->findBy(['publicado' => true]);

        $categoriasArray = [];
        foreach ($categorias as $categoria) {
            $subcategoriasArray = [];

            foreach ($categoria->getSubcategorias() as $subcategoria) {
                $subcategoriasArray[] = [
                    'id' => $subcategoria->getId(),
                    'nombre' => $subcategoria->getNombre(),
                    'slug' => $subcategoria->getSlug()
                ];
            }

            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug(),
                'subcategorias' => $subcategoriasArray
            ];
        }

        return $this->json($categoriasArray);
    }

    #[Route('/api/categorias_tienda/select', name: 'select_categorias_tienda', methods: ['GET'])]
    #[OA\Tag(name: 'Categorias')]
    #[OA\Response(
        response: 200,
        description: 'Lista de categorias de una tienda disponibles para seleccionar al crear un producto',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list_c_tienda(CategoriasTiendaRepository $categoriasRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $categorias = $categoriasRepository->findBy(['Tiendas' => $tienda]);

        $categoriasArray = [];
        foreach ($categorias as $categoria) {

            $categoriasArray[] = [
                'id' => $categoria->getId(),
                'nombre' => $categoria->getNombre(),
                'slug' => $categoria->getSlug(),
            ];
        }

        return $this->json($categoriasArray);
    }



    #[Route('/api/productos/delete/{id}', name: 'app_productos_delete', methods: ['DELETE'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Elimina un producto por id de la tienda',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete($id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $producto = $entityManager->getRepository(Productos::class)->findOneBy(['id' => $id, 'tienda' => $tienda]);

        $pedidos = $entityManager->getRepository(DetallePedido::class)->findBy(['IdProductos' => $producto]);

        if (!empty($pedidos)) {
            return $this->errorsInterface->error_message('No se puede eliminar el producto porque tiene pedidos asociados', Response::HTTP_BAD_REQUEST);
        }



        if (!$producto) {
            return $this->errorsInterface->error_message('producto no encontrado', Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($producto);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Eliminado');
    }


    #[Route('/api/productos/visibilidad/{id}', name: 'visivilidad_producto', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Cambiar el estado de un producto a disponible o no por su id',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cambiar_visivilidad(EntityManagerInterface $entityManager, $id): Response
    {
        $producto = $entityManager->getRepository(Productos::class)->find($id);
        if (!$producto) {
            return $this->errorsInterface->error_message('producto no encontrado', Response::HTTP_BAD_REQUEST);
        }

        $producto->setDisponibilidadProducto(!$producto->isDisponibilidadProducto());
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Desactivado');
    }


    #[Route('/api/productos/visibilidad/variante/{id}', name: 'visivilidad_variante_producto', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Cambiar el estado de una variente de un producto a disponible o no por su id',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cambiar_visivilidad_variante(EntityManagerInterface $entityManager, $id): Response
    {
        $producto = $entityManager->getRepository(Variaciones::class)->find($id);
        if (!$producto) {
            return $this->errorsInterface->error_message('Variante no encontrada', Response::HTTP_BAD_REQUEST);
        }

        $producto->setDisponibilidadVariante(!$producto->isDisponibilidadVariante());
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Desactivado');
    }


    #[Route('/api/productos/borrador/{id}', name: 'update_borrador_producto', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Cambiar el estado de un poducto a borrador a false)',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function borrador_p(EntityManagerInterface $entityManager, $id = null): Response
    {
        $producto = $entityManager->getRepository(Productos::class)->find($id);
        if (!$producto) {
            return $this->errorsInterface->error_message('Producto no encontrado', Response::HTTP_BAD_REQUEST);
        }

        $producto->setBorrador(false);
        $producto->setFromForm(true);
        $entityManager->flush();

        return $this->errorsInterface->succes_message('Actualizado');
    }


    #[Route('/api/productos/borrador', name: 'borrador_producto', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Obtener producto borrador mas reciente.',
    )]
    #[OA\Parameter(
        name: "from_form",
        in: "query",
        description: "Filtra los borradores que tienen true o false en from_form."
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function get_borrador_producto(Request $request, EntityManagerInterface $entityManager, UrlGeneratorInterface $router): Response
    {
        $allowedParams = [
            'from_form'
        ];

        // Obtener todos los parámetros enviados
        $queryParams = array_keys($request->query->all());

        // Detectar parámetros no permitidos
        $invalidParams = array_diff($queryParams, $allowedParams);

        if (!empty($invalidParams)) {
            return $this->errorsInterface->error_message(
                'Parámetros no permitidos',
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $f_form = filter_var($request->query->get('from_form'), FILTER_VALIDATE_BOOLEAN) ? filter_var($request->query->get('from_form'), FILTER_VALIDATE_BOOLEAN) : null;
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $producto = $entityManager->getRepository(Productos::class)->findOneBy(['borrador' => true, "from_form" => $f_form, 'tienda' => $tienda], ['fecha_registro_producto' => 'DESC']);


        if (!$producto) {
            $productosArray = null;
        } else {

            $productosArray = $this->productoInterface->vista_privada($producto);
        }



        return $this->json($productosArray);
    }


    #[Route('/api/productos/lista_borrador', name: 'lista_borrador', methods: ['GET'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\Response(
        response: 200,
        description: 'Obtener lista de  borradores)',
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function lista_borrador(EntityManagerInterface $entityManager, UrlGeneratorInterface $router, Request $request): Response
    {

        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        $productos = $entityManager->getRepository(Productos::class)->findBy(['borrador' => true, "from_form" => false, 'tienda' => $tienda], ['fecha_registro_producto' => 'DESC']);

        $productosArray = [];
        foreach ($productos as $producto) {

            if (!$producto->getProductosTipo() || ($producto->getProductosTipo()->getId() != 4 && $producto->getProductosTipo()->getId() != 3)) {
                $productosArray[] = $this->productoInterface->vista_privada($producto);
            }
        }
        return $this->json($productosArray);
    }


    #[Route('/api/multi_products/delete', name: 'app_delete_multiple_products', methods: ['DELETE'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Eliminar múltiples productos de la tienda',
        content: new Model(type: DeleteMultipleProductosType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteMultiple(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Obtenemos el usuario y su tienda
        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        // Validación: Si no hay tienda asociada, devolvemos un error
        if (!$tienda) {
            return $this->errorsInterface->error_message('No se encontró la tienda asociada al usuario.', Response::HTTP_NOT_FOUND);
        }

        // Creamos y procesamos el formulario
        $form = $this->createForm(DeleteMultipleProductosType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Obtenemos los productos seleccionados desde el formulario
            $productos = $form->get('productos')->getData();

            // Verificamos si todos los productos pertenecen a la tienda
            $productosNoValidos = [];
            foreach ($productos as $producto) {
                if ($producto->getTienda() !== $tienda) {
                    $productosNoValidos[] = $producto->getNombreProducto(); // O usa algún identificador del producto
                }
            }

            // Si hay productos no válidos, devolvemos un error y listamos los productos
            if (!empty($productosNoValidos)) {
                return $this->errorsInterface->error_message(
                    'Algunos productos no pertenecen a tu tienda y no pueden ser eliminados.',
                    Response::HTTP_BAD_REQUEST,
                    'productos_no_validos',
                    $productosNoValidos
                );
            }

            // Si todos los productos son válidos, procedemos con la eliminación
            foreach ($productos as $producto) {
                $entityManager->remove($producto);
            }

            $entityManager->flush();


            return $this->errorsInterface->succes_message('Productos eliminados correctamente');
        }

        // Manejo de errores del formulario
        return $this->errorsInterface->form_errors($form);
    }


    #[Route(path: '/api/update/multi_products', name: 'update_multi-products', methods: ['PUT'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Actualizar multiples productos',
        content: new Model(type: MultiProductsType::class)
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update_masive_products(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $user = $this->getUser();
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        // Validación: Si no hay tienda asociada, devolvemos un error
        if (!$tienda) {
            return $this->errorsInterface->error_message('No se encontró la tienda asociada al usuario.', Response::HTTP_NOT_FOUND);
        }


        $form = $this->createForm(MultiProductsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $productos = $form->get('productos')->getData();
            $categorias = $form->get('categorias')->getData();
            $subcategorias = $form->get('subcategorias')->getData();
            $categoriasTienda = $form->get('categoriasTiendas')->getData();
            $subcategoriasTiendas = $form->get('subcategoriasTiendas')->getData();
            $direccion = $form->get('direcciones')->getData();
            $marca = $form->get('marcas')->getData();
            $entrgas = $form->get('entrgas_tipo')->getData();
            $productosVentas = $form->get('productos_ventas')->getData();
            $estado = $form->get('estado')->getData();
            $disponible = $form->get('disponibilidad_producto')->getData();
            $borrador = $form->get('borrador')->getData();

            $productosNoValidos = [];
            foreach ($productos as $producto) {
                if ($producto->getTienda() !== $tienda) {
                    $productosNoValidos[] = $producto->getNombreProducto(); // O usa algún identificador del producto
                }
            }

            // Si hay productos no válidos, devolvemos un error y listamos los productos
            if (!empty($productosNoValidos)) {
                return $this->errorsInterface->error_message(
                    'Algunos productos no pertenecen a tu tienda y no pueden ser actualizados.',
                    Response::HTTP_BAD_REQUEST,
                    'productos_no_validos',
                    $productosNoValidos
                );
            }

            foreach ($productos as $producto) {


                if (!empty($categorias)) {
                    foreach ($categorias as $categoria) {
                        $producto->addCategoria($categoria);
                    }
                }

                if (!empty($subcategorias)) {
                    foreach ($subcategorias as $subcategoria) {
                        $producto->addSubcategoria($subcategoria);
                    }
                }


                if (!empty($categoriasTienda)) {
                    foreach ($categoriasTienda as $c_tiendas) {
                        $producto->addCategoriasTienda($c_tiendas);
                    }
                }

                if (!empty($subcategoriasTiendas)) {
                    foreach ($subcategoriasTiendas as $subcategoriasTienda) {
                        $producto->addSubcategoriasTienda($subcategoriasTienda);
                    }
                }

                if (!empty($direccion)) {
                    $producto->setDirecciones($direccion);
                }

                if (!empty($marca)) {
                    $producto->setMarcas($marca);
                }

                if (!empty($entrgas)) {
                    $producto->setEntrgasTipo($entrgas);
                }

                if (!empty($productosVentas)) {
                    $producto->setProductosVentas($productosVentas);
                }

                if (!empty($estado)) {
                    $producto->setEstado($estado);
                }

                if (!empty($disponible)) {
                    $producto->setDisponibilidadProducto($disponible);
                }


                if ($disponible !== null) {
                    $producto->setDisponibilidadProducto($disponible);
                }

                if ($borrador !== null) {
                    $producto->setBorrador($borrador);
                }


                $entityManager->persist($producto);
            }

            $entityManager->flush();

            return $this->errorsInterface->succes_message('Productos actualizados correctamente');

        }

        return $this->errorsInterface->form_errors($form);

    }

}
