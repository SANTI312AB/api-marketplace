<?php

namespace App\Service;

use App\Entity\GeneralesApp;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PlacetoPayService
{
    private $params;
    private $entityManager;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $entityManager)
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    public function processPayment(
        string $nombre,
        string $apellido = null,
        string $email,
        string $dni,
        string $documento,
        string $telefono,
        string $nVenta,
        float $total,
        float $subtotal,
        float $costo_envio=null,
        float $impuestos,
        string $returnUrl
    ) {
        
        $generales = $this->entityManager->getRepository(GeneralesApp::class);
        $auth = $generales->getLoginPTP();
        $apiUrl = $generales->data_url()."/session";

        $fecha = date("Y-m-d H:i:s");
        $expiracion = date('c', strtotime($fecha . "+ 10 minutes"));
        $parametros = $generales->crearPagoPTP(
            $nombre,
            $apellido,
            $email,
            $dni,
            $documento,
            $telefono,
            $nVenta,
            "Compra",
            $total,
            $subtotal,
            $costo_envio,
            $impuestos,
            $expiracion,
            $returnUrl,
            "72.60.66.73"
        );

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($auth, $parametros)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }


        if ($httpCode != 200) {
            throw new Exception('Respuesta Paypal '. 'Code'. $httpCode . 'response : ' . $response);
        }

    
        return $response;
    }
}
