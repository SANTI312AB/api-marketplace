<?php   

namespace App\Service;

use App\Entity\GeneralesApp;
use App\Entity\Pedidos;
use CURLFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;


class FacturadorService
{
    private $em;

    private array $configFacturador = [];
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'Facturador_SODIG']);
        foreach ($generales as $parametro) {
        $this->configFacturador[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
    }


    public function logo_facturador(?UploadedFile $file): JsonResponse
    {
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Debe enviar un archivo de logo válido'], 400);
        }

        $apiKey = $this->configFacturador['SecretKey'] ?? null;
        if (empty($apiKey)) {
            return new JsonResponse(['error' => 'API Key no configurada en el sistema'], 500);
        }

        $url = rtrim($this->configFacturador['Url'], '/') . '/api/Configuration/logo';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "api-key: $apiKey",
            ],
            CURLOPT_POSTFIELDS => [
                'ImageFile' => new CURLFile(
                    $file->getPathname(),
                    $file->getMimeType() ?: 'application/octet-stream',
                    $file->getClientOriginalName()
                ),
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return new JsonResponse(['error' => "Error de conexión: $error"], 500);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Intentar decodificar JSON seguro
        $decoded = json_decode($response, true);
        

        return new JsonResponse($decoded, $httpCode > 0 ? $httpCode : 500);
    }


    public function config_sing(UploadedFile $file): JsonResponse
    {
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Debe enviar un archivo de logo válido'], 400);
        }

        $payload = [
            'SignFile' => new CURLFile(
                    $file->getPathname(),
                    $file->getMimeType() ?: 'application/octet-stream',
                    $file->getClientOriginalName()
                ),
            'SignPassword' => $this->configFacturador['Password'] ?? '',
        ];

        $apiKey = $this->configFacturador['SecretKey'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($this->configFacturador['Url']) . '/api/Configuration/sign',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "api-key: $apiKey"
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return new JsonResponse(['error' => "Error de conexión: $error"], 500);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Intentar decodificar JSON seguro
        $decoded = json_decode($response, true);

        // Éxito o error de API
        return new JsonResponse($decoded, $httpCode > 0 ? $httpCode : 500);
    }

    private function mapPorcentajeImpuesto(int $valor): string
    {
        return match ($valor) {
            0 => "Cero",
            5 => "Cinco",
            12 => "Doce",
            13 => "Trece",
            14 => "Catorce",
            15 => "Quince",
            default => "NoAplica"
        };
    }

    public function mapIdenticador(string $valor): string
    {
        return match ($valor) {
            'CI' => "Cedula",
            'RUC' => "RUC",
            'PPN' => "Pasaporte",
            default => "NoAplica"
        };
    }


    public function añadir_facturador(Pedidos $pedido)
    {
        $detalles = [];
        $subtotalHeader = 0.0;
        $TOTAL_IVA = 0.0;

        $fecha_pedido = $pedido->getFechaPedido()
            ? $pedido->getFechaPedido()->format('Y-m-d')
            : '';

        $fecha_pago = $pedido->getFechaPago()
            ? $pedido->getFechaPago()->format('Y-m-d')
            : '';

        // IVA del pedido
        $porcentajeIva = (int) $pedido->getIvaAplicado();

        foreach ($pedido->getDetallePedidos() as $detalle) {
            
            /*subtotalLinea = $detalle->getSubtotalUnitario() * $detalle->getCantidad();
            $ivaLinea = $subtotalLinea * ($porcentajeIva / 100);
            $TOTAL_IVA += $ivaLinea;
            $subtotalHeader += $detalle->getSubtotalUnitario() * $detalle->getCantidad();*/
            $TOTAL_IVA += $detalle->getImpuesto();
            //$subtotalHeader += $detalle->getSubtotal();
    

            $detalles[] = [
                "descripcion" => $detalle->getNombreProducto(),
                "precioUnitario" => $detalle->getSubtotalUnitario(),
                "codigo" => $detalle->getCodigoProducto(),
                "cantidad" => $detalle->getCantidad(),
                "impuesto" => $detalle->getImpuesto(), // aquí sí
                "tipoImpuesto" => 'IVA',
                "porcentajeImpuesto" => $this->mapPorcentajeImpuesto($porcentajeIva)
            ];
        }

        // Subtotal del header (redondeado)

        $payload = [
            "fecha" => $fecha_pago ?: $fecha_pedido,
            "subtotal" => $pedido->getSubtotal(),
            "tipoPago" => 'Credito',
            "cliente" => [
                "nombre" => $pedido->getCustomer(),
                "identificacion" => $pedido->getDniCustomer(),
                "tipoIdentificacion" => $this->mapIdenticador(
                    (string) $pedido->getLogin()->getUsuarios()->getTipoDocumento()
                ),
                "direccion" => $pedido->getDireccionPrincipal() . '-' . $pedido->getDireccionSecundaria(),
                "email" => $pedido->getLogin()->getEmail()
            ],
            "detalles" => $detalles
        ];

        // 🔑 api-key
        $apiKey = $this->configFacturador['SecretKey'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($this->configFacturador['Url'], '/') . '/api/Billing',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "api-key: $apiKey"
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            $pedido->setEstadoFacturador("Error al conectar con el facturador: $err");
            $this->em->flush();
            return new JsonResponse([
                "message" => "Error al conectar con el facturador: $err"
            ], 500);
            //throw new \RuntimeException("Error al conectar con el facturador: $err");
        }
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 || $httpCode === 201) {
            $claveFacturador = $result['data']['claveAcceso'] ?? '';
            $numeroFactura = $result['data']['numeroFactura'] ?? '';
            $pedido->setClaveFacturador($claveFacturador);
            $pedido->setNFactura($numeroFactura);
            $pedido->setFechaFacturacion(new \DateTime());
            $pedido->setEstadoFacturador('Enviado a Facturador');
            $this->em->flush();
        } else {
            $errorPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $pedido->setEstadoFacturador($result['message'].'-'.$errorPayload ?? 'Error desconocido');
            $this->em->flush();
            //throw new \RuntimeException($result['message'] ?? 'Error desconocido');
        }

        //return true;

        return new JsonResponse([
            'api' => $result,
            'total_iva' => round($TOTAL_IVA, 2, PHP_ROUND_HALF_UP),
            'payload' => $payload

        ], $httpCode);
    }

    public function añadir_facturador_command(Pedidos $pedido)
    {
        $detalles = [];
         $subtotalHeader = 0.0;
        $TOTAL_IVA = 0.0;

        $fecha_pedido = $pedido->getFechaPedido()
            ? $pedido->getFechaPedido()->format('Y-m-d')
            : '';

        $fecha_pago = $pedido->getFechaPago()
            ? $pedido->getFechaPago()->format('Y-m-d')
            : '';

        // IVA del pedido
        $porcentajeIva = (int) $pedido->getIvaAplicado();

        foreach ($pedido->getDetallePedidos() as $detalle) {
               /*subtotalLinea = $detalle->getSubtotalUnitario() * $detalle->getCantidad();
            $ivaLinea = $subtotalLinea * ($porcentajeIva / 100);
            $TOTAL_IVA += $ivaLinea;
            $subtotalHeader += $detalle->getSubtotalUnitario() * $detalle->getCantidad();*/
            $TOTAL_IVA += $detalle->getImpuesto();
            //$subtotalHeader += $detalle->getSubtotal();
    

            $detalles[] = [
                "descripcion" => $detalle->getNombreProducto(),
                "precioUnitario" => $detalle->getSubtotalUnitario(),
                "codigo" => $detalle->getCodigoProducto(),
                "cantidad" => $detalle->getCantidad(),
                "impuesto" => $detalle->getImpuesto(), // aquí sí
                "tipoImpuesto" => 'IVA',
                "porcentajeImpuesto" => $this->mapPorcentajeImpuesto($porcentajeIva)
            ];
        }

        // Subtotal del header (redondeado)

        $payload = [
            "fecha" => $fecha_pago ?: $fecha_pedido,
            "subtotal" => $pedido->getSubtotal(),
            "tipoPago" => 'Credito',
            "cliente" => [
                "nombre" => $pedido->getCustomer(),
                "identificacion" => $pedido->getDniCustomer(),
                "tipoIdentificacion" => $this->mapIdenticador(
                    (string) $pedido->getLogin()->getUsuarios()->getTipoDocumento()
                ),
                "direccion" => $pedido->getDireccionPrincipal() . '-' . $pedido->getDireccionSecundaria(),
                "email" => $pedido->getLogin()->getEmail()
            ],
            "detalles" => $detalles
        ];

        // 🔑 api-key
        $apiKey = $this->configFacturador['SecretKey'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($this->configFacturador['Url'], '/') . '/api/Billing',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "api-key: $apiKey"
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            $pedido->setEstadoFacturador("Error al conectar con el facturador: $err");
            $this->em->flush();
            /*return new JsonResponse([
                "message" => "Error al conectar con el facturador: $err"
            ], 500);*/
            throw new \RuntimeException("Error al conectar con el facturador: $err");
        }
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 || $httpCode === 201) {
            $claveFacturador = $result['data']['claveAcceso'] ?? '';
            $numeroFactura = $result['data']['numeroFactura'] ?? '';
            $pedido->setClaveFacturador($claveFacturador);
            $pedido->setNFactura($numeroFactura);
            $pedido->setFechaFacturacion(new \DateTime());
            $pedido->setEstadoFacturador('Enviado a Facturador');
            $this->em->flush();
        } else {
            $errorPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $pedido->setEstadoFacturador($result['message'].'-'.$errorPayload ?? 'Error desconocido');
            $this->em->flush();
            /*return new JsonResponse([
                'api' => $result,
                'payload' => $payload
            ], $httpCode);*/
            throw new \RuntimeException($result['message'] ?? 'Error desconocido');
        }
        return true;
        /*return new JsonResponse([
            'api' => $result,
            'payload' => $payload
        ], $httpCode);*/
    }

    public function verificar_factura(string $claveAcceso): array
    {
        if (empty($claveAcceso)) {
            return [
                "success" => false,
                "message" => "Debe enviar el parámetro claveAcceso"
            ];
        }

        $apiKey = $this->configFacturador['SecretKey'];
        $url = $this->configFacturador['Url'] . "/api/Billing/status?claveAcceso=" . urlencode($claveAcceso);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "api-key: $apiKey"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                "success" => false,
                "message" => $error,
                "status" => $httpCode
            ];
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                "success" => false,
                "message" => "Error al decodificar JSON: " . json_last_error_msg(),
                "raw" => $response,
                "status" => $httpCode
            ];
        }

        return $result;
    }

}