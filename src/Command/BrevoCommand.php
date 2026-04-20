<?php

namespace App\Command;

use App\Entity\GeneralesApp;
use App\Entity\Login;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;


#[AsCommand(
    name: 'app:add-contacts-brevo',
    description: 'Cargar usuarios Shopby en la lista de contactos de Brevo'
)]
class BrevoCommand extends Command
{
    
    private $container;
    private $parameters;
     
    private  $entityManager;
   
    private array $configApp = [];
    public function __construct(EntityManagerInterface $entityManager,ContainerInterface $container,ParameterBagInterface $parameters)
    {
        $this->container = $container;
        $this->parameters = $parameters;
        $this->entityManager = $entityManager;
        $generales= $this->entityManager->getRepository(GeneralesApp::class)->findBy(['nombre'=>'brevo']);
        foreach ($generales as $parametro) {
        $this->configApp[$parametro->getAtributoGeneral()] = $parametro->getValorGeneral();
        }
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Cargar usuarios Shopby en la lista de contactos de Brevo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $usuarios = $em->getRepository(Login::class)->findBy(['vericacion'=> 7]);

        if(!$usuarios){
            $io = new SymfonyStyle($input, $output);
            $this->logCommandOutput('No hay usuarios registrados para cargar en Brevo.', Command::FAILURE);
            $io->error('No hay usuarios registrados para cargar en Brevo.');
            return Command::FAILURE;
        }

        $apiUrl=$this->configApp['Url'].'/contacts/import';
        $key= $this->configApp['SecretKey'];
     
        foreach ($usuarios as $usuario) {
            
            $data = [
                "listIds" => [3],
                "emailBlacklist" => false,
                "smsBlacklist" => false,
                "updateExistingContacts" => true,
                "emptyContactsAttributes" => false,
                "notifyUrl" => "https://shopby.com.ec",
                "jsonBody" => [
                    [
                        "email" => $usuario->getEmail(),
                        "attributes" => [
                            "LTNAME" => $usuario->getUsuarios() ? $usuario->getUsuarios()->getApellido():'',
                            "FNAME" => $usuario->getUsuarios() ? $usuario->getUsuarios()->getNombre():'',
                            "COUNTRY" => "EC",
                            "SMS" => $usuario->getUsuarios() ? $usuario->getUsuarios()->getCelular():''
                        ]
                    ]
                ]
            ];
         
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS , json_encode(array_merge($data)));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Accept: application/json",
                        "api-key: ".$key,
                        "Content-Type: application/json"
                ]);
        
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                 
                
                 if (curl_errno($ch)) {
                    
                    $io = new SymfonyStyle($input, $output);
                    $this->logCommandOutput('Error al conectar a la api', Command::FAILURE);
                    $io->warning('Error al conectar a la api');
                    return Command::FAILURE;
                 }
         
                 if ($httpCode!=202){

                    $io = new SymfonyStyle($input, $output);
                    $this->logCommandOutput( 'Error: '.json_encode(json_decode($response), JSON_PRETTY_PRINT), Command::FAILURE);
                    $io->warning('Error: '.json_encode(json_decode($response), JSON_PRETTY_PRINT));
                    return Command::FAILURE;
                 }
     
        }

    

        $io = new SymfonyStyle($input, $output);
        $this->logCommandOutput('Usuarios importados a Brevo.', Command::SUCCESS);
        $output->writeln('Usuarios importados a Brevo.');
    
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
