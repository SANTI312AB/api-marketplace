<?php
namespace App\Service;

use App\Entity\GeneralesApp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WhatsAppApiService
{
    private $client;
    private $phoneNumberId;
    private $apiToken;
    private $apiVersion;
    private $catalogId;
    private $em;
    private array $configApp = [];

    public function __construct(ParameterBagInterface $params,EntityManagerInterface $em)
    {
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'whatsapp']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
        $this->client = new Client([
            'base_uri' => $this->configApp['Url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->configApp['Token'],
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->phoneNumberId = $this->configApp['Login'];
        $this->apiVersion = $this->configApp['Api_Version'];
        $this->catalogId = $this->configApp['Catalog'];
    }


    public function webhook_data(){
        $key= $this->configApp['SecretKey'];
        return $key;
    }

    public function sendTemplateMessage(
        string $to, 
        string $templateName, 
        string $languageCode, 
        array $parameters = []
    ): array {
        if (empty($templateName)) {
            throw new \InvalidArgumentException("Nombre de plantilla requerido");
        }
    
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->formatNumber($to),
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => []
                ]
            ];
    
            // ✅ Agregar parámetros si la plantilla tiene variables
            if (!empty($parameters)) {
                $payload['template']['components'][] = [
                    'type' => 'body',
                    'parameters' => array_map(fn($param) => ['type' => 'text', 'text' => $param], $parameters)
                ];
            }
    
            $response = $this->client->post("{$this->apiVersion}/{$this->phoneNumberId}/messages", [
                'json' => $payload
            ]);
    
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new \RuntimeException("Error WhatsApp: " . ($errorResponse['error']['message'] ?? 'Desconocido'));
        }
    }
    
    
    
    
    // Valida URLs en el mensaje
    private function hasUrls(string $message): bool
    {
        return preg_match('/https?:\/\/\S+/i', $message) === 1;
    }
    
    // Formatea el número internacional
    private function formatNumber(string $number): string
    {
        return preg_replace('/[^0-9]/', '', $number); // Elimina todo excepto dígitos
    }

    public function post(string $url, array $data): array
    {
        try {
            $response = $this->client->post($url, $data);
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new \RuntimeException(
                $errorResponse['error']['message'] ?? 'Error desconocido en WhatsApp API',
                $e->getCode()
            );
        }
    }

    public function add_product(array $productData): array
    {
        // Validación básica
        if (empty($productData['id']) || empty($productData['name'])) {
            throw new \InvalidArgumentException("Datos de producto incompletos");
        }

        $payload = [
            "name" => $productData['name'],
            "description" => $productData['description'] ?? '',
            "retailer_id" => (string)$productData['id'],
            "price" => (int)($productData['price'] * 100), // Convertir a centavos
            "currency" => $productData['currency'] ?? 'USD',
            "image_url" => $productData['image_url'] ?? '',
            "availability" => ($productData['stock'] ?? 0) > 0 ? "in stock" : "out of stock"
        ];

        return $this->post(
            "{$this->apiVersion}/{$this->catalogId}/products",
            ['json' => $payload]
        );
    }
}