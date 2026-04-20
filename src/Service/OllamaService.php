<?php 

namespace App\Service;

use App\Entity\GeneralesApp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class OllamaService
{

    private $params;

    private $em;

    private array $configApp = [];

    public function __construct(ParameterBagInterface $params,EntityManagerInterface $em){
        $this->params= $params;
        $this->em= $em;
        $generales= $this->em->getRepository(GeneralesApp::class)->findBy(['nombre'=>'ollama']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }

    }

     public function llamarOllamaConCurl($preguntaUsuario,$contextoRAG)
    {
        
        $ollamaUrl = $this->configApp['Url'].'/api/chat'; // o 'http://localhost:11434/api/chat'
        //$nombreModeloOllama = 'deepseek-r1'; // Reemplaza con el nombre exacto de tu modelo
        $nombreModeloOllama ='deepseek-llm:7b'; // Reemplaza con el nombre exacto de tu modelo
        // --- INICIO DE LA MODIFICACIÓN ---
        $instruccionIdioma = "IMPORTANTE: Responde SIEMPRE en ESPAÑOL y en un ÚNICO PÁRRAFO CORTO. ";
        $promptSistema = $instruccionIdioma .
            "Eres un asistente especializado de Shopby Ecuador. Reglas estrictas:\n" .
            "1. Usa EXCLUSIVAMENTE la información del CONTEXTO entre >>> y <<<\n" .
            "2. Si la respuesta no está en el CONTEXTO, di exactamente: 'No tengo información sobre eso'\n" .
            "3. Formato: 1 párrafo breve (3-5 líneas máximo)\n" .
            "4. Sin listas, puntos ni formatos especiales\n\n" .
            "CONTEXTO ENTRE >>>\n" . $contextoRAG . "\n<<< FIN DEL CONTEXTO";

        $payload = [
            'model' => $nombreModeloOllama,
            'messages' => [
                [
                    'role' => 'system',
                    // Usamos el prompt del sistema modificado
                    'content' => $promptSistema
                ],
                [
                    'role' => 'user',
                    'content' => $preguntaUsuario // Asegúrate que esta pregunta también esté en español
                ]
            ],
            'stream' => false,
            'options' => [
               'num_gpu'=> 100,
               'temperature' => 0.0, // Puedes probar con una temperatura un poco más baja para respuestas más deterministas
               'num_predict' => 200
            ]
        ];

        $jsonData = json_encode($payload);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $ollamaUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 800);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            error_log("Error cURL al conectar con Ollama: " . $errorMsg);
            return "Error: No se pudo conectar con el servicio de asistencia (cURL: " . $errorMsg . ")";
        }

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $body = json_decode($response, true);
            if (isset($body['message']['content'])) {
                return $body['message']['content'];
            } elseif (isset($body['error'])) {
                error_log("Error devuelto por API de Ollama: " . $body['error']);
                return "Error: El modelo de asistencia devolvió un error: " . $body['error'];
            }
            error_log("Respuesta inesperada de Ollama: " . $response);
            return "Error: Respuesta inesperada del servicio de asistencia.";
        } else {
            error_log("Error HTTP de Ollama: " . $httpCode . " - Respuesta: " . $response);
            return "Error: El servicio de asistencia no está disponible (HTTP " . $httpCode . ")";
        }
    }
}

