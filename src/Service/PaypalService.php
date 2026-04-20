<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\GeneralesAppRepository;
use Exception;

class PaypalService
{
    private $params;
    private $generalesAppRepository;

    public function __construct(ParameterBagInterface $params, GeneralesAppRepository $generalesAppRepository)
    {
        $this->params = $params;
        $this->generalesAppRepository = $generalesAppRepository;
    }

    public function data_url(){

        $data_url= $this->generalesAppRepository->findOneBy(['nombre'=>'paypal','atributoGeneral'=>'Url']);
        return $data_url->getValorGeneral();
    }

    public function createOrder(
        string $nVenta,
        float $total,
        float $subtotal,
        float $impuestos,
        float $costoEnvio=null,
        string $ref = "?origen=cancel"
    ) {
        $authToken = $this->getToken();

        $formattedTotal = sprintf("%.2f", round($total, 2));
        $formattedSubtotal = sprintf("%.2f", round($subtotal, 2));
        $formattedImpuestos = sprintf("%.2f", round($impuestos, 2));
        $formattedShipping = sprintf("%.2f", round($costoEnvio, 2));

        $front_url= $this->generalesAppRepository->findOneBy(['nombre'=>'front','atributoGeneral'=>'Url']);
        $url = $this->data_url() . "/v2/checkout/orders";

        // Prepare request payload
        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => $nVenta,
                   /* "payee" => [
                        "email_address" => 'jarevalo@sodigsa.com'
                    ],*/
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $formattedTotal,
                        "details" => [
                            "subtotal" => $formattedSubtotal,
                            "tax" => $formattedImpuestos,
                            "shipping" => $formattedShipping,
                        ],
                    ],
                ],
            ],
            "application_context" => [
                "return_url" => $front_url->getValorGeneral() . "/checkout/resumen/" . $nVenta,
                "cancel_url" => $front_url->getValorGeneral() . "/checkout/resumen/" . $nVenta . $ref,
            ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                        "brand_name" => "Shopby",
                        "locale" => "en-US",
                        "landing_page" => "LOGIN",
                        "shipping_preference" => "GET_FROM_FILE",
                        "user_action" => "PAY_NOW",
                    ],
                ],
            ],
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array_merge($payload)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "PayPal-Request-Id: " . $nVenta,
            "Authorization: Bearer " . $authToken
        ]);

        // Execute request
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Error Paypal: ' . curl_error($ch));
        }

        

        if ($httpCode != 200) {
            throw new Exception('Respuesta Paypal ' . $httpCode . ' : ' . $result);
        }


        return $result; // Return the PayPal response as an array
    }

    public function getToken(): string
    {
        // --- INICIO DE LA OPTIMIZACIÓN ---

        // 1. Hacemos UNA sola consulta para traer todos los parámetros de PayPal.
        $paypalParams = $this->generalesAppRepository->findBy(['nombre' => 'paypal']);

        // 2. Creamos un mapa de configuración para acceder fácilmente a los valores.
        $config = [];
        foreach ($paypalParams as $param) {
            $config[$param->getAtributoGeneral()] = $param->getValorGeneral();
        }

        // 3. Verificamos que las credenciales existen antes de continuar.
        if (!isset($config['Login']) || !isset($config['SecretKey'])) {
            throw new Exception('Las credenciales de PayPal (Login o SecretKey) no están configuradas.');
        }

        // --- FIN DE LA OPTIMIZACIÓN ---

        $url = $this->data_url() . "/v1/oauth2/token";

        // Usamos los valores del mapa de configuración
        $clientId = $config['Login'];
        $secret = $config['SecretKey'];

        $postData = "grant_type=client_credentials";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, value: 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Error Paypal: ' . curl_error($ch));
        }

        if ($httpCode != 200) {
            throw new Exception('Respuesta Paypal ' . 'Code' . $httpCode . 'response : ' . $result);
        }

        curl_close($ch);

        $json = json_decode($result);

        if (!isset($json->access_token)) {
            throw new Exception('Access token not found in PayPal response');
        }

        return $json->access_token;
    }

    
}
