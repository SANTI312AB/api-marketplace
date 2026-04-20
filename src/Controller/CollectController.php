<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\FuncionesEspecialesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Interfaces\ErrorsInterface;
use OpenApi\Attributes as OA;

final class CollectController extends AbstractController
{
    private ErrorsInterface $errorInterface;

    public function __construct(ErrorsInterface $errorInterface)
    {
        $this->errorInterface = $errorInterface;
    }

    #[Route('/collect', name: 'api_collect', methods: ['POST'])]
    #[OA\Tag(name: 'EvenData')]
    public function collect(
        Request $request,
        EntityManagerInterface $em,
        FuncionesEspecialesRepository $funcionesRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (
            !$data ||
            !isset($data['eventName'], $data['timestamp'], $data['user'], $data['sessionId'], $data['eventData'])
        ) {
            return $this->errorInterface->error_message(
                'Invalid payload',
                400
            );
        }

        // 🚀 Traer configuración desde la tabla FuncionesEspeciales (IDs 6,7,8)
        $config = $funcionesRepo->getValidationConfig();

        $maxKeys  = $config['maxKeys'] ?? 10;
        $maxDepth = $config['maxDepth'] ?? 3;
        $onlyStr  = $config['onlyStrings'] ?? true;

        $eventData = $data['eventData'];

        // 1) Validar número de keys
        if (count($eventData) > $maxKeys) {
            return $this->errorInterface->error_message(
                "Too many keys in eventData (max = $maxKeys)",
                400,
                'eventData',
                $eventData
            );
        }

        // 2) Validar profundidad
        if ($this->arrayDepth($eventData) > $maxDepth) {
            return $this->errorInterface->error_message(
                "eventData too deep (max depth = $maxDepth)",
                400,
                'eventData',
                $eventData
            );
        }

        // 3) Validar valores string
        if ($onlyStr) {
            foreach ($eventData as $key => $value) {
                if (!is_string($value)) {
                    return $this->errorInterface->error_message(
                        "Value for '$key' must be string",
                        400,
                        $key,
                        $value
                    );
                }
                if (preg_match('/[{}()]/', $value)) {
                    return $this->errorInterface->error_message(
                        "Invalid characters in value for '$key'",
                        400,
                        $key,
                        $value
                    );
                }
            }
        }

        // ✅ Guardar evento
        $event = new Event();
        $event->setEventName($data['eventName']);
        $event->setTimestamp(new \DateTimeImmutable($data['timestamp']));
        $event->setUser($data['user']); // 👈 corregido, en tu entity es userId
        $event->setSessionId($data['sessionId']);
        $event->setEventData($eventData);

        if (isset($data['sdkVersion'])) {
            $event->setSdkVersion($data['sdkVersion']);
        }
        if (isset($data['clientId'])) {
            $event->setClientId($data['clientId']);
        }
        if (isset($data['schemaVersion'])) {
            $event->setSchemaVersion($data['schemaVersion']);
        }

        $em->persist($event);
        $em->flush();

        return $this->errorInterface->succes_message(
            'Event saved successfully'
        );
    }

    private function arrayDepth(array $array): int
    {
        $maxDepth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->arrayDepth($value) + 1;
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }
        return $maxDepth;
    }
}
