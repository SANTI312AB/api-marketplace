<?php


namespace App\Command;

use App\Entity\Carrito;
use App\Entity\Estados;
use App\Entity\Pedidos;
use App\Service\PaypalService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\GeneralesAppRepository;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:actualizar-pedidos-paypal',
    description: 'Actualiza el estado de los pedidos pendientes desde la API de Paypal'
)]
class PaypalCommand extends Command
{
    private $container;
    private $parameters;
    private $generalesAppRepository;

    private $paypalService;

    public function __construct(GeneralesAppRepository $generalesAppRepository,ContainerInterface $container,ParameterBagInterface $parameters,PaypalService $paypalService)
    {
        $this->container = $container;
        $this->parameters = $parameters;
        $this->generalesAppRepository = $generalesAppRepository;
        $this->paypalService= $paypalService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Actualiza el estado de los pedidos pendientes desde la API de Paypal');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $ingresado= $entityManager->getRepository(Estados::class)->findOneBy(['id' => 19]);
        $pendiente = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 23]);
        $cancelado = $entityManager->getRepository(Estados::class)->findOneBy(['id' => 24]);
    
        $pedidos = $entityManager->getRepository(Pedidos::class)->findBy(['estado' => 'PENDING','metodo_pago'=>3]);
        
        $cartsToDelete=[];

        foreach ($pedidos as $pedido) {
        $user = $pedido->getLogin();
        $carrito = $entityManager->getRepository(Carrito::class)->findOneBy(['login' => $user]);

        $id = $pedido->getReferenciaPedido();
        $n_venta = $pedido->getNVenta();
        $url = $this->parameters->get('paypal_url')."/v2/checkout/orders/".$id;

        $auth = $this->paypalService->getToken();
      
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $auth
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $rest= $result;

        $json = json_decode($rest);



        if ($json->status === 'APPROVED') {

            $url = $this->parameters->get('paypal_url')."/v2/checkout/orders/$id/capture";

            $auth = $this->paypalService->getToken();
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
             curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "PayPal-Request-Id: " . $n_venta, // Usar solo el n_venta como PayPal-Request-Id
                "Authorization: Bearer " . $auth,
            ]);
    
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            $rest = $result;
    
            $j = json_decode($rest);

            if($j->status === 'COMPLETED'){
               $pedido->setEstado("APPROVED"); 
               $pedido->setEstadoEnvio($ingresado);
               $pedido->setEstadoRetiro($ingresado);
               $entityManager->flush();

               if ($carrito !== null) {

                $cartsToDelete[] = $carrito; 

               }

    
            }
            
        }elseif($json->status === 'COMPLETED'){
            $pedido->setEstado('APPROVED');
            $pedido->setEstadoEnvio($ingresado);
            $pedido->setEstadoRetiro($ingresado);
            $entityManager->flush();
        }elseif($json->status === 'APPROVED' || $json->status === 'PAYER_ACTION_REQUIRED' ){
            $pedido->setEstado('PENDING');
            $pedido->setEstadoEnvio($pendiente);
            $pedido->setEstadoRetiro($pendiente);
            $entityManager->flush();
        }
       

       }

       foreach ($cartsToDelete as $cartToDelete) {
        $entityManager->remove($cartToDelete);
        $entityManager->flush();
      }

    // Lógica de tu función aquí
    $output->writeln('La tarea se ejecutó con éxito.');

    return Command::SUCCESS;
}


}


