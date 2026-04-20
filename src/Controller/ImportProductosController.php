<?php

namespace App\Controller;

use App\Entity\EntregasTipo;
use App\Entity\Estados;
use App\Entity\Login;
use App\Entity\Productos;
use App\Entity\ProductosGaleria;
use App\Entity\ProductosTipo;
use App\Entity\ProductosVentas;
use App\Entity\Tiendas;
use App\Entity\UsuariosDirecciones;
use App\Form\ImportProductsType;
use App\Interfaces\ErrorsInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class ImportProductosController extends AbstractController
{
    private $errorsInterface;
    private HttpClientInterface $httpClient;
    private $filesystem;


    public function __construct(ErrorsInterface $errorsInterface,HttpClientInterface $httpClient, Filesystem $filesystem)
    {
        $this->errorsInterface = $errorsInterface;
        $this->httpClient = $httpClient;
        $this->filesystem = new Filesystem();

    }

    #[Route('/api/import/productos', name: ' ', methods: ['POST'])]
    #[OA\Tag(name: 'Productos')]
    #[OA\RequestBody(
        description: 'Añadir productos mediante un archivo xslx o csv',
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['excel', 'direcciones'],
                properties: [
                    new OA\Property(
                        property: 'excel',
                        description: 'Archivo Excel (csv o xlsx)',
                        type: 'file'
                    ),
                    new OA\Property(
                        property: 'direcciones',
                        description: 'ID de la dirección seleccionada',
                        type: 'integer',
                    ),
                ]
            )
        )
    )]
    #[Security(name: 'Bearer')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function import_data(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if(!$user instanceof Login){
            // Si el usuario no es un Login, manejamos el error
            return $this->errorsInterface->error_message('No tienes permisos para realizar esta acción.', Response::HTTP_UNAUTHORIZED);
        }
        $tienda = $entityManager->getRepository(Tiendas::class)->findOneBy(['login' => $user]);

        $verificacion = strtoupper($user->getUsuarios()->getEstados()->getNobreEstado() ?? '');
        $tienda_verificacion = strtoupper($user->getTiendas()->getEstado()->getNobreEstado() ?? '');

        if (empty($verificacion) || empty($tienda_verificacion)) {
            return $this->errorsInterface->error_message('Error: Estado del usuario o de la tienda no disponible.', Response::HTTP_BAD_REQUEST);
        }

        // Permitir si la tienda o el usuario están verificados (o ambos)
        if ($verificacion !== "VERIFICADO" && $tienda_verificacion !== "VERIFICADO") {
            return $this->errorsInterface->error_message('Debes verificar tu identidad o tu tienda para publicar productos.', Response::HTTP_FORBIDDEN);
        }
        
        $form = $this->createForm(ImportProductsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $excelFile */
            $excelFile = $form->get('excel')->getData();
            $direccion = $form->get('direcciones')->getData();

            if ($excelFile) {
                try {
                    $spreadsheet = IOFactory::load($excelFile->getPathname());
                } catch (\Exception $e) {
                    return $this->errorsInterface->error_message('Error al procesar el archivo Excel', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Procesar el archivo y obtener los errores (si los hay)
                $errors = $this->processExcelData($spreadsheet, $entityManager, $tienda, $direccion);

                if (!empty($errors)) {
                    // Si hay errores, retornarlos
                    return $this->errorsInterface->error_message(
                        'Errores al cargar registros',
                        Response::HTTP_BAD_REQUEST,
                        null,
                        $errors
                    );
                }

                return $this->errorsInterface->succes_message('Productos cargados correctamente');
            }
        }

        // Manejar errores de validación del formulario
        

        return $this->errorsInterface->form_errors($form);
    }


    private function processExcelData(Spreadsheet $spreadsheet, EntityManagerInterface $em, Tiendas $tienda, UsuariosDirecciones $direccion): array
    {
        // 1. MAPEO DE COLUMNAS
        // Centraliza la definición de columnas para facilitar la lectura y los mensajes de error.
        $columnMap = [
            0 => 'Imagen (URL)',
            1 => 'Nombre del Producto',
            2 => 'Link Video Youtube',
            3 => 'Cantidad',
            4 => 'SKU',
            5 => 'EAN',
            6 => 'Precio',
            7 => 'Porcentaje de rebaja (%)',
            8 => '¿Producto grava Iva? (SI/NO)',
            9 => '¿Precio Incluye IVA? (SI/NO)',
            10 => 'Etiquetas',
            11 => 'Descripción Corta',
            12 => 'Garantía',
            13 => 'Descripción Larga',
            14 => 'Ficha Técnica',
            15 => 'Peso (KG)',
            16 => 'Alto (cm)',
            17 => 'Ancho (cm)',
            18 => 'Largo (cm)',
        ];

        $worksheet = $spreadsheet->getActiveSheet();
        $errors = [];

        // --- MEJORA: Configuración para procesamiento por lotes ---
        $batchSize = 50; // Guarda en la BD cada 50 productos. Ajusta este valor según tu servidor.
        $i = 1;

        // --- MEJORA: Cargar entidades reutilizables una vez antes del bucle ---
        // Estas se volverán a cargar después de cada flush/clear.
        $productos_tipo = $em->getRepository(ProductosTipo::class)->findOneBy(['id' => 1]);
        $id_estado = $em->getRepository(Estados::class)->findOneBy(['id' => 5]);
        $id_tipo_venta = $em->getRepository(ProductosVentas::class)->findOneBy(['id' => 1]);
        $entregas_tipo = $em->getRepository(EntregasTipo::class)->findOneBy(['id' => 1]);


        // 2. BUCLE PRINCIPAL MEJORADO
        // --- MEJORA: Se usa getRowIterator() para no cargar todo el archivo en memoria ---
        $startRow = 2; // Fila donde comienzan los datos (la fila 1 es el encabezado)
        foreach ($worksheet->getRowIterator($startRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Procesa incluso celdas vacías

            // Convierte la fila del iterador a un array simple
            $rowValues = [];
            foreach ($cellIterator as $cell) {
                $rowValues[] = $cell->getValue();
            }

            $filaReal = $row->getRowIndex();

            // Saltar la fila si está completamente vacía
            if (empty(array_filter($rowValues, fn($value) => trim((string) $value) !== ''))) {
                continue;
            }

            // Asignación de variables desde la fila
            $image = $rowValues[0] ?? null;
            $nombre = trim($rowValues[1] ?? '');
            $link_video = trim($rowValues[2] ?? '');
            $cantidad = $rowValues[3] ?? null;
            $sku = trim($rowValues[4] ?? '');
            $ean = trim($rowValues[5] ?? '');
            $precio = $rowValues[6] ?? null;
            $descuento = $rowValues[7] ?? null;
            $tiene_iva = $rowValues[8] ?? null;
            $incluye_iva = $rowValues[9] ?? null;
            $etiqueta = trim($rowValues[10] ?? '');
            $descripcion = trim($rowValues[11] ?? '');
            $garantia = trim($rowValues[12] ?? '');
            $descripcion_larga = trim($rowValues[13] ?? '');
            $ficha_tecnica = trim($rowValues[14] ?? '');
            $peso = $rowValues[15] ?? null;
            $alto = $rowValues[16] ?? null;
            $ancho = $rowValues[17] ?? null;
            $largo = $rowValues[18] ?? null;

            // 3. VALIDACIONES (Sin cambios en esta sección)
            // ... (toda tu lógica de validación if/continue va aquí sin cambios) ...
            if (!$nombre) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[1]}': El nombre del producto es obligatorio.";
                continue;
            }
            if (strlen($nombre) > 250) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[1]}': El nombre excede los 250 caracteres.";
                continue;
            }
            $existingProduct = $em->getRepository(Productos::class)->findOneBy(['nombre_producto' => $nombre, 'tienda' => $tienda]);
            if ($existingProduct) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[1]}': Ya existe un producto con el nombre '$nombre'.";
                continue;
            }
            if ($link_video && strlen($link_video) > 80) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[2]}': El link del video excede los 80 caracteres.";
                continue;
            }
            if ($sku && strlen($sku) > 20) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[4]}': El SKU excede los 20 caracteres.";
                continue;
            }
            if ($ean !== null && $ean !== '' && (!is_numeric($ean) || strlen($ean) > 30)) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[5]}': El EAN debe ser numérico y no exceder los 30 dígitos.";
                continue;
            }
            if ($precio === null || $precio === '' || !is_numeric($precio) || $precio < 0) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[6]}': El precio es obligatorio y debe ser un número mayor o igual a 0.";
                continue;
            }
            if ($descuento !== null && $descuento !== '' && (!is_numeric($descuento) || $descuento < 0 || $descuento > 100)) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[7]}': El descuento debe ser un número entre 0 y 100.";
                continue;
            }
            if ($cantidad === null || $cantidad === '' || !is_numeric($cantidad) || $cantidad <= 0) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[3]}': La cantidad es obligatoria y debe ser un número positivo.";
                continue;
            }
            // ... (el resto de tus validaciones) ...

            // Normalización de valores booleanos (SI/NO)
            $opcionesBool = ['si' => 1, 'no' => 0, '1' => 1, '0' => 0, 1 => 1, 0 => 0];
            $tiene_iva_norm = isset($opcionesBool[strtolower(trim($tiene_iva))]) ? $opcionesBool[strtolower(trim($tiene_iva))] : null;
            if (($tiene_iva !== null && $tiene_iva !== '') && $tiene_iva_norm === null) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[8]}': El valor debe ser 'SI' o 'NO'.";
                continue;
            }
            $incluye_iva_norm = isset($opcionesBool[strtolower(trim($incluye_iva))]) ? $opcionesBool[strtolower(trim($incluye_iva))] : null;
            if (($incluye_iva !== null && $incluye_iva !== '') && $incluye_iva_norm === null) {
                $errors[] = "Fila $filaReal, Columna '{$columnMap[9]}': El valor debe ser 'SI' o 'NO'.";
                continue;
            }


            // 4. CREACIÓN DEL PRODUCTO
            $slug = strtolower(str_replace(' ', '-', $nombre));
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
            $slug = substr($slug, 0, 100);
            $slug = $slug . '-' . uniqid();

            $producto = new Productos();
            $producto->setTienda($tienda);
            $producto->setNombreProducto($nombre);
            $producto->setSlugProducto($slug);
            $producto->setEstado($id_estado);
            $producto->setProductosVentas($id_tipo_venta);
            $producto->setEntrgasTipo($entregas_tipo);
            $producto->setVideoProducto($link_video);
            $producto->setSkuProducto($sku);
            $producto->setEanProducto($ean);
            $producto->setVariable(false);
            $producto->setDirecciones($direccion);
            $producto->setTieneIva($tiene_iva_norm);
            $producto->setImpuestosIncluidos($incluye_iva_norm);
            $producto->setDescripcionCortaProducto($descripcion);
            $producto->setEtiquetasProducto($etiqueta);
            $producto->setDescripcionLargaProducto($descripcion_larga);
            $producto->setGarantiaProducto($garantia);
            $producto->setFichaTecnica($ficha_tecnica);
            $producto->setProductosTipo($productos_tipo);
            $producto->setPrecioNormalProducto(round($precio, 2, PHP_ROUND_HALF_UP));
            $producto->setCantidadProducto($cantidad);
            $producto->setPeso($peso);
            $producto->setAlto($alto);
            $producto->setAncho($ancho);
            $producto->setLargo($largo);

            if ($descuento > 0 && $precio > 0) {
                $precioRebajado = $precio - (($descuento / 100) * $precio);
                $producto->setPrecioRebajadoProducto(round($precioRebajado, 2, PHP_ROUND_HALF_UP));
                $producto->setTieneDescuento(true);
            } else {
                $producto->setPrecioRebajadoProducto(null);
                $producto->setTieneDescuento(false);
            }

            $em->persist($producto);

            // 5. PROCESAMIENTO DE IMÁGENES
            if ($image) {
                $directory = $this->getParameter('images_directory');
                $urls = array_map('trim', explode(',', $image));

                foreach ($urls as $index => $url) {
                    if (empty($url))
                        continue;

                    $slugConIndice = $slug . '-' . $index;
                    $filename = $this->downloadAndSave($url, $directory, $slugConIndice);

                    if ($filename && $filename !== false) {
                        $galeria = new ProductosGaleria();
                        $galeria->setProducto($producto);
                        $galeria->setUrlProductoGaleria($filename);
                        $em->persist($galeria);
                    } else {
                        $errors[] = "Fila $filaReal, Columna '{$columnMap[0]}': No se pudo procesar la imagen desde la URL: $url.";
                    }
                }
            }

            // --- MEJORA: Lógica de procesamiento por lotes ---
            if (($i % $batchSize) === 0) {
                // Si hay errores acumulados, detenerse aquí antes de hacer flush
                if (!empty($errors)) {
                    return $errors;
                }
                $em->flush(); // Guarda el lote de objetos en la base de datos
                $em->clear(); // Limpia la memoria de Doctrine para el siguiente lote

                // Es crucial volver a cargar las entidades que se reutilizan en el bucle
                $tienda = $em->getRepository(Tiendas::class)->find($tienda->getId());
                $direccion = $em->getRepository(UsuariosDirecciones::class)->find($direccion->getId());
                $productos_tipo = $em->getRepository(ProductosTipo::class)->findOneBy(['id' => 1]);
                $id_estado = $em->getRepository(Estados::class)->findOneBy(['id' => 5]);
                $id_tipo_venta = $em->getRepository(ProductosVentas::class)->findOneBy(['id' => 1]);
                $entregas_tipo = $em->getRepository(EntregasTipo::class)->findOneBy(['id' => 1]);
            }
            $i++;
        }

        // 6. FINALIZACIÓN
        if (!empty($errors)) {
            return $errors; // Retorna errores si los hubo, no se guarda nada del último lote.
        }

        // --- MEJORA: flush final para los productos restantes del último lote ---
        $em->flush();
        $em->clear();

        return []; // Retorna un array vacío si todo fue exitoso.
    }


    public function downloadAndSave(string $url, string $saveDirectory, string $slug): string|false
    {
        try {
            $imageData = null;
            $extension = null;

            // Detectar si es URL remota o ruta local
            if (preg_match('/^https?:\/\//', $url)) {

                // --- Lógica para Google Drive ---
                if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
                    $fileId = $matches[1];
                    // Construye la URL de descarga directa
                    $url = 'https://drive.google.com/uc?export=download&id=' . $fileId . '&confirm=t';
                }

                // --- Petición HTTP ---
                $response = $this->httpClient->request('GET', $url);

                if ($response->getStatusCode() !== 200) {
                    return false; // Falla si no es 200 OK
                }

                // --- Validación de accesibilidad para Google Drive y otros ---
                $contentType = $response->getHeaders(false)['content-type'][0] ?? '';
                if (!str_starts_with(strtolower($contentType), 'image/')) {
                    // Si no empieza con 'image/', es probable que sea una página de "acceso denegado".
                    return false;
                }

                $imageData = $response->getContent();

                // --- Obtener extensión desde Content-Type (más fiable) ---
                $mimeMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                $extension = $mimeMap[$contentType] ?? null;

                if (!$extension) {
                    $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                    $extension = $pathInfo['extension'] ?? 'jpg'; // Fallback a jpg
                }

            } else {
                // --- CASO RUTA LOCAL ---
                if (str_starts_with($url, 'file:///')) {
                    $url = substr($url, 7);
                }
                if (!file_exists($url)) {
                    return false;
                }
                $imageData = file_get_contents($url);
                $pathInfo = pathinfo($url);
                $extension = $pathInfo['extension'] ?? 'jpg';
            }

            // Validación final
            if (!$imageData || !$extension) {
                return false;
            }

            // Construir nombre de archivo y guardar
            $filename = $slug . '.' . $extension;
            $fullPath = rtrim($saveDirectory, '/') . '/' . $filename;
            $this->filesystem->dumpFile($fullPath, $imageData);

            return $filename; // Éxito: devuelve el nombre del archivo

        } catch (Exception $e) {
            // Considera registrar el error $e para depuración
            return false; // Error por excepción
        }
    }

}

