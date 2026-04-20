<?php

namespace App\Controller;

use App\Form\OllamaForm;
use App\Interfaces\ErrorsInterface;
use App\Service\OllamaService;
use App\Service\RagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

final class OllamaController extends AbstractController
{
    private $request;
    private $errorsInterface;

    private $ollamaService;

    private $ragService;

    public function __construct(RequestStack $request,ErrorsInterface $errorsInterface,OllamaService $ollamaService,RagService $ragService)
    {
        $this->request = $request->getCurrentRequest();
        $this->errorsInterface = $errorsInterface;
        $this->ollamaService= $ollamaService;
        $this->ragService= $ragService;
    }

    
    #[Route('/ollama', name: 'app_ollama', methods:['POST'])]
    #[OA\Tag(name: 'Ollama')]
    #[OA\RequestBody(
        description: 'Asistente virtual ia.',
        content: new Model(type: OllamaForm::class)
    )]
    public function ollama_app(): Response
    {
        $form = $this->createForm(OllamaForm::class);
        $form->handleRequest($this->request);
        if ($form->isSubmitted() && $form->isValid()) {
          
            $pregunta = $form->get('pregunta')->getData();


            if ($this->ragService->esBusquedaProductos($pregunta)) {
                $respuestaModelo = $this->ragService->buscarProductos($pregunta);
        
            }else{
                $contextoCompleto = $this->ragService->generarContextoTexto();

                $chunks = $this->dividirContextoEnChunks($contextoCompleto);

                // 3. Generar embedding para la pregunta
                $embeddingPregunta = $this->ragService->embed_data($pregunta);

                // 4. Encontrar los chunks más relevantes
                $contextoRelevante = $this->encontrarChunksRelevantes($pregunta, $embeddingPregunta, $chunks);


                $respuestaModelo = $this->ollamaService->llamarOllamaConCurl($pregunta, $contextoRelevante);

                // --- 3. Devolver la respuesta ---
                if (strpos(strtolower($respuestaModelo), 'error') !== false || strpos(strtolower($respuestaModelo), 'no se pudo conectar') !== false) {
                    return $this->errorsInterface->error_message($respuestaModelo, Response::HTTP_INTERNAL_SERVER_ERROR, $contextoRelevante); // O un código de error más específico
                }

            }

            return $this->json(['respuesta' => $respuestaModelo])->setStatusCode(Response::HTTP_OK);

        }

        return $this->errorsInterface->form_errors($form);
    }

    private function dividirContextoEnChunks(string $contexto): array
    {
        $chunks = [];
        $secciones = explode("\n\n", $contexto);

        foreach ($secciones as $seccion) {
            // Dividir cada sección en párrafos
            $parrafos = explode(". ", $seccion);
            $chunkActual = '';

            foreach ($parrafos as $parrafo) {
                if (str_word_count($chunkActual . $parrafo) < 50) { // Límite de 50 palabras por chunk
                    $chunkActual .= $parrafo . '. ';
                } else {
                    $chunks[] = trim($chunkActual);
                    $chunkActual = $parrafo . '. ';
                }
            }

            if (!empty($chunkActual)) {
                $chunks[] = trim($chunkActual);
            }
        }

        return $chunks;
    }

    private function encontrarChunksRelevantes(string $pregunta, array $embeddingPregunta, array $chunks): string
    {
        $chunksConSimilitud = [];

        // Calcular similitud para cada chunk
        foreach ($chunks as $chunk) {
            $embeddingChunk = $this->ragService->embed_data($chunk);
            $similitud = $this->calcularSimilitudCoseno($embeddingPregunta, $embeddingChunk);

            $chunksConSimilitud[] = [
                'texto' => $chunk,
                'similitud' => $similitud
            ];
        }

        // Ordenar por similitud descendente
        usort($chunksConSimilitud, function ($a, $b) {
            return $b['similitud'] <=> $a['similitud'];
        });

        // Seleccionar los 3 chunks más relevantes
        $topChunks = array_slice($chunksConSimilitud, 0, 3);

        // Mejorar con búsqueda de palabras clave como fallback
        if (empty($topChunks) || max(array_column($topChunks, 'similitud')) < 0.3) {
            return $this->busquedaPorPalabrasClave($pregunta, $chunks);
        }

        // Combinar los chunks relevantes
        return implode("\n\n", array_column($topChunks, 'texto'));
    }

    private function calcularSimilitudCoseno(array $vecA, array $vecB): float
    {
        $productoPunto = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($vecA); $i++) {
            $productoPunto += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $productoPunto / (sqrt($normA) * sqrt($normB));
    }

    private function busquedaPorPalabrasClave(string $pregunta, array $chunks): string
    {
        $palabrasClave = [
            'pago' => ['pago', 'pagado', 'pagar', 'tarjeta', 'paypal'],
            'envio' => ['envío', 'envios', 'enviar','envían', 'entrega', 'recibir','paquetes'],
            'contacto' => ['contacto', 'email', 'correo', 'teléfono', 'llamar', 'dirección']
        ];

        $chunksRelevantes = [];

        foreach ($chunks as $chunk) {
            foreach ($palabrasClave as $tema => $palabras) {
                foreach ($palabras as $palabra) {
                    if (stripos($pregunta, $palabra) !== false && stripos($chunk, $tema) !== false) {
                        $chunksRelevantes[] = $chunk;
                        break 2; // Salir de ambos bucles
                    }
                }
            }
        }

        return !empty($chunksRelevantes)
            ? implode("\n\n", $chunksRelevantes)
            : "Información general: " . implode("\n\n", array_slice($chunks, 0, 3));
    }

}
