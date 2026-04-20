<?php

namespace App\Command;

use App\Entity\CommandLog;
use App\Entity\Servientrega;
use App\Entity\Pedidos;
use App\Entity\Estados;
use App\Service\ServientregaService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DetallePedidoRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:update-pedidos-servientrega',
    description: 'Actualizar estado envio pedidos servientrega.'
)]
class ServientregaCommand extends Command
{

    private EntityManagerInterface $entityManager;

    private DetallePedidoRepository $detallePedidoRepository;


    private $servientregaService;

    public function __construct(EntityManagerInterface $entityManager,DetallePedidoRepository $detallePedidoRepository,ServientregaService $servientregaService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->detallePedidoRepository = $detallePedidoRepository;
        $this->servientregaService= $servientregaService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Actualizar estado envio pedidos servientrega.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entregado = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 22]);
        $en_camino = $this->entityManager->getRepository(Estados::class)->findOneBy(['id' => 21]);
        $respuesta = $this->servientregaService->soap();
                $valor_wsl = $respuesta['wsl'];
                $valor_soap = $respuesta['soap'];
            

        $io = new SymfonyStyle($input, $output);
        $guias = $this->entityManager->getRepository(Servientrega::class)->findBy(['metodo_envio' => 1, 'anulado' => false], ['fecha_registro' => 'DESC']);
        $results = [];

        foreach ($guias as $guia) {

            $codigo = $guia->getCodigoServientrega();
            $pedido = $guia->getPedido();
            if ($pedido instanceof Pedidos && $pedido->getEstado() == 'APPROVED' && $pedido->getMetodoEnvio()->getId() === 1 && $pedido->getEstadoEnvio()->getId() !== 22) {
                sleep(1); 
                $client = new \SoapClient(null, [
                    'location' => $valor_wsl,
                    'uri' => $valor_soap,
                    'trace' => true,
                    'exceptions' => true,
                ]);
                // Construye el XML manualmente según la documentación
                $requestXml = <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                          xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                          xmlns:ws="https://servientrega-ecuador.appsiscore.com/app/ws/">
            <soapenv:Header/>
            <soapenv:Body>
                <ws:ConsultarGuiaImagen soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                    <guia xsi:type="xsd:string">$codigo</guia>
                </ws:ConsultarGuiaImagen>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;


                try {
                    
                    $response = $client->__doRequest($requestXml, $valor_wsl, 'ConsultarGuiaImagen', SOAP_1_1);

                    if (!mb_detect_encoding($response, 'UTF-8', true)) {
                        $response = mb_convert_encoding($response, 'UTF-8', 'ISO-8859-1');
                    }

                    // Primero, intentamos corregir el DOCTYPE si es que la respuesta es XML
                    $response = preg_replace('/^<!DOCTYPE[^>]+>/i', '', $response);

                    $xmlResponse = @simplexml_load_string($response);

                    if ($xmlResponse === false) {
                        $logFileName = 'servientrega_error_response_' . $codigo . '.txt';
                        file_put_contents('var/log/' . $logFileName, $response);
                        // ============================================

                        $errorMsg = 'Error al procesar la respuesta (XML inválido o HTML recibido) - guia:' . $codigo;
                        $this->logCommandOutput($errorMsg . ' | Respuesta guardada en var/log/' . $logFileName, Command::FAILURE);
                        $io->error($errorMsg);
                        continue;
                    }


                    $resultXml = (string) $xmlResponse->xpath('//Result')[0] ?? null;
                    if ($resultXml === false || empty($resultXml)) {
                        $errorMsg = 'No se encontró la etiqueta <Result> en la respuesta SOAP';
                        $this->logCommandOutput($errorMsg . ' | Respuesta cruda: ' . substr($response, 0, 500), Command::FAILURE);
                        $io->error($errorMsg);
                        continue; // saltar a la siguiente guía
                    }

                    $decodedXml = html_entity_decode($resultXml);
                    $resultParsed = simplexml_load_string($decodedXml, "SimpleXMLElement", LIBXML_NOCDATA);

                    if ($resultParsed === false) {
                        $errorMsg = 'Error al procesar el contenido del <Result>';
                        $this->logCommandOutput($errorMsg . ' | Contenido: ' . substr($decodedXml, 0, 500), Command::FAILURE);
                        $io->error($errorMsg);
                        continue;
                    }

                    $resultJson = json_decode(json_encode($resultParsed), true);

                    $filteredResult = [
                        'EstAct' => $resultJson['EstAct'] ?? null,
                    ];
                    $movInfo = $resultJson['Mov']['InformacionMov'] ?? [];

                    $tieneMovimientos = is_array($movInfo) && count($movInfo) > 0;

                    $estadosEntregado = [
                        'Reportado Entregado',
                        'Certificacion de Prueba de Entrega CL',
                        'Entrega Digitalizada en Centro Logistico CL'
                    ];

                    // Buscar fechas de entrega
                    $fechasEntrega = [];
                    foreach ($movInfo as $movimiento) {
                        $nomMov = $movimiento['NomMov'] ?? '';
                        if (in_array($nomMov, $estadosEntregado)) {
                            $fecMov = $movimiento['FecMov'] ?? null;
                            if ($fecMov) {
                                try {
                                    $fechasEntrega[] = new DateTime($fecMov);
                                } catch (\Exception $e) {
                                    // Ignorar formato inválido
                                }
                            }
                        }
                    }

                    if (
                        in_array($filteredResult['EstAct'], $estadosEntregado) ||
                        strpos($filteredResult['EstAct'], 'Reportado Entregado') !== false ||
                        strpos($filteredResult['EstAct'], 'Certificacion de Prueba de Entrega CL') !== false ||
                        strpos($filteredResult['EstAct'], 'Entrega Digitalizada en Centro Logistico CL') !== false
                    ) {
                        $pedido->setEstadoEnvio($entregado);
                        if (!empty($fechasEntrega)) {
                            $fechaEntrega = max($fechasEntrega);
                            $pedido->setFechaEntregaAdomicilio($fechaEntrega);
                        }
                        $this->entityManager->flush();
                        /*$user = $pedido->getTienda()->getLogin(); 
                        $this->gestionarTransacciones->calcularTransacciones($user);*/
                    } elseif ($tieneMovimientos) {
                        // Si hay movimientos, actualizar a "en camino"
                        $pedido->setEstadoEnvio($en_camino);
                        $pedido->setFechaEnCamino(new DateTime());
                        $this->entityManager->flush();
                    }

                } catch (\SoapFault $e) {
                    $this->logCommandOutput('Error con SOAP servientrega: ' . $e->getMessage(), Command::FAILURE);
                    $io->error('Error con SOAP servientrega: ' . $e->getMessage());
                    continue;
                }

            }

        }

        $io = new SymfonyStyle($input, $output);
        $this->logCommandOutput('Lista de guías procesada correctamente.', Command::SUCCESS);
        $io->success('Lista de guías procesada correctamente.');

        return Command::SUCCESS;
    }

    private function logCommandOutput(string $errorMessage, int $exitCode): void
    {
        $logEntry = new CommandLog();
        $logEntry->setCommandName($this->getName());
        $logEntry->setArguments(json_encode([])); // Puedes ajustar esto según los argumentos reales
        $logEntry->setErrorMessage($errorMessage);
        $logEntry->setExitCode($exitCode);
        $logEntry->setStartTime(new \DateTime());
        $logEntry->setEndTime(new \DateTime());
    
        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }
}