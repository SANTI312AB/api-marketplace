<?php

namespace App\Service;

use App\Entity\GeneralesApp;
use App\Entity\MetodosEnvio;
use App\Entity\MetodosPago;
use App\Entity\Productos;
use App\Entity\ShopbyInfo;
use Psr\Cache\CacheItemPoolInterface; // Asegúrate de inyectar esto
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Interfaces\ProductoInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RagService
{
    private $em;

    private $cache;
    private const CACHE_TTL = 86400; // 1 hora, por ejemplo

    private $productoInterface;
    private $params;

    private array $configApp = [];

    public function __construct(EntityManagerInterface $em, CacheItemPoolInterface $cache, ProductoInterface $productoInterface,ParameterBagInterface $params)
    {
        $this->em= $em;
        $this->cache= $cache;
        $this->productoInterface= $productoInterface;
        $this->params= $params;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'ollama']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
    }


    public function esBusquedaProductos(string $pregunta): bool
    {
        // Palabras clave principales que activan búsqueda
        $palabrasClave = [
            'buscar',
            'busca',
            'busco',
            'encuentra',
            'necesito',
            'encontrar',
            'producto',
            'productos',
            'artículo',
            'artículos',
            'item',
            'hay',
            'tienen',
            'tienes',
            'disponible',
            'modelo',
            'marca',
            'recomienda',
            'sugiere',
            'opciones',
            'variedad',
            'catálogo',
            'selección',
            'comparar'
        ];

        // Verificar presencia de al menos 1 palabra clave
        foreach ($palabrasClave as $palabra) {
            if (stripos($pregunta, $palabra) !== false) {
                return true;
            }
        }

        // Detección de patrones específicos sin categorías
        // Patrón mejorado (detecta y captura)
        $patrones = [
            '/\b(qué|cuáles)\s+([^\?]+)\s+ten(e|éis|emos|go)\b/i', // "qué productos tienen", "cuáles artículos tienes"
            '/\b(ver|mostrar|listar|revisar)\s+([^\?]+)\b/i',      // "mostrar laptops", "ver celulares"
            '/\b(productos|artículos)\s+de\s+([^\?]+)\b/i'         // "productos de tecnología"
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $pregunta)) {
                return true;
            }
        }

        return false;
    }

    public function buscarProductos(string $pregunta)
    {
        // Limpieza mejorada conservando palabras sustantivas
        $palabrasEliminar = [
            'buscar',
            'busca',
            'busco',
            'encuentra',
            'necesito',
            'encontrar',
            'producto',
            'productos',
            'artículo',
            'artículos',
            'item',
            'hay',
            'tienen',
            'tienes',
            'disponible',
            'modelo',
            'marca',
            'recomienda',
            'sugiere',
            'opciones',
            'variedad',
            'catálogo',
            'selección',
            'comparar',
            'por',
            'favor',
            'puedes',
            'quiero',
            'deseo',
            'muestra',
            'dame',
            'ver',
            'listar',
            'revisar',
            'qué',
            'cuáles',
            'quien',
            'tengo'
        ];

        // Tokenizar y filtrar
        $palabras = preg_split('/\s+/', $pregunta);
        $palabrasFiltradas = array_filter($palabras, function ($palabra) use ($palabrasEliminar) {
            $palabra = preg_replace('/[?¿.,!;]/', '', strtolower($palabra));
            return !in_array($palabra, $palabrasEliminar) && strlen($palabra) > 2;
        });

        $queryLimpio = implode(' ', $palabrasFiltradas);

        // Si el query limpio está vacío, usar palabras clave de la pregunta
        if (empty($queryLimpio)) {
            $palabrasClaveReserva = ['tecnología', 'electrónica', 'ropa', 'hogar', 'muebles'];
            foreach ($palabrasClaveReserva as $palabra) {
                if (stripos($pregunta, $palabra) !== false) {
                    $queryLimpio = $palabra;
                    break;
                }
            }

            // Si aún está vacío, usar la pregunta completa
            if (empty($queryLimpio)) {
                $queryLimpio = $pregunta;
            }
        }

        // Buscar productos en la base de datos
        $productos = $this->em->getRepository(Productos::class)->search_product($queryLimpio);
        $data = [];

        foreach ($productos as $producto) {
            $data[] = $this->productoInterface->vista_minima($producto);
        }

        if (!$data) {
            return 'No se encontraron productos relacionados con "' . htmlspecialchars($queryLimpio) . '"';
        }

        return $data;
    }

    private function obtenerInfoMetodosPago():string
    {
        $cacheKey = 'metodos_pago_activos_contexto';
        $item = $this->cache->getItem($cacheKey);
        if (!$item->isHit()) {
            $metodos_pago = $this->em->getRepository(MetodosPago::class)->findBy(['activo' => true]);
            $m = [];
            foreach ($metodos_pago as $metodo) {
                $m[] = $metodo->getNombre() . ":" . $metodo->getDescripcion();
            }
            $info = implode("; ", $m);
            $item->set($info);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);
            return $info;
        }
        return $item->get();
    }

    private function obtenerInfoMetodosEnvio():string
    {
        $cacheKey = 'metodos_envio_activos_contexto';
        $item = $this->cache->getItem($cacheKey);
        if (!$item->isHit()) {
            $metodos_envio = $this->em->getRepository(MetodosEnvio::class)->findBy(['activo' => true]);
            $mv = [];
            foreach ($metodos_envio as $metodo) {
                $mv[] = $metodo->getNombre() . ":" . $metodo->getDescripcion();
            }
            $info = implode("; ", $mv);
            $item->set($info);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);
            return $info;
        }
        return $item->get();
    }

    private function obtenerInfoShopbyPorClave($claveInfo):string
    {
        // Normalizar la clave para usarla en la cache key y evitar caracteres especiales.

        $cacheKey = 'shopby_info_' . $claveInfo;

        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {

            $infoEntity = $this->em->getRepository(ShopbyInfo::class)->findOneBy(['nombre' => $claveInfo]);

            $descripcion = $infoEntity ? $infoEntity->getDescripcion() : ''; // Devuelve vacío si no se encuentra

            $item->set($descripcion);
            $item->expiresAfter(self::CACHE_TTL); // TTL más largo para info estática
            $this->cache->save($item);

            return $descripcion;
        }

        return $item->get();
    }



    public function  generarContextoTexto(){
        $contexto = "";

        $contexto .= "=== MÉTODOS DE PAGO ===\n";
        $contexto .= $this->obtenerInfoMetodosPago() . "\n\n";

        $contexto .= "=== MÉTODOS DE ENVÍO ===\n";
        $contexto .= $this->obtenerInfoMetodosEnvio() . "\n\n";

        $contexto .= "=== EMAIL DE CONTACTO ===\n";
        $contexto .= $this->obtenerInfoShopbyPorClave('email') . "\n\n";

        $contexto .= "=== TELÉFONO DE CONTACTO ===\n";
        $contexto .= $this->obtenerInfoShopbyPorClave('teléfono') . "\n\n";

        $contexto .= "=== DIRECCIÓN ===\n";
        $contexto .= $this->obtenerInfoShopbyPorClave('dirección') . "\n\n";

        $contexto .= "=== PRESENTACIÓN DE SHOPBY ===\n";
        $contexto .= $this->obtenerInfoShopbyPorClave('presentación');

        return $contexto;
    }



    public function embed_data(string $texto): ?array
    {

        $url =$this->configApp['Url'].'/api/embeddings'; // o 'http://localhost:11434/api/chat'
        //$nombreModeloOllama = 'deepseek-r1'; // Reemplaza con el nombre exacto de tu modelo
        $model = 'mxbai-embed-large';

        
        $data = json_encode([
            'model' => $model,
            'prompt' => $texto
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true)['embedding'] ?? null;

    }

}