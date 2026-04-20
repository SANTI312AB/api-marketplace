<?php

namespace App\Command;

use App\Entity\Carrito;
use App\Service\DynamicMailerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\CommandLog;
use App\Entity\Impuestos;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:notify-carrito',
    description: 'Notifica a los usuarios que no han marcado como entregado un pedido'
)]
class NotifyCarritoCommand extends Command
{
    private $entityManager;
    private $mailer;
    private $parameters;

    public function __construct(EntityManagerInterface $entityManager, DynamicMailerFactory $mailer, ParameterBagInterface $parameters)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->parameters = $parameters;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Notifica a los usuarios que no han marcado como entregado un pedido')
            ->addOption('hours', null, InputOption::VALUE_OPTIONAL, 'Número de horas desde la fecha de creacioin del carrito',0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $impusto= $this->entityManager->getRepository(Impuestos::class)->findOneBy(['id'=>1]);
        $iva= $impusto->getIva();
        $io = new SymfonyStyle($input, $output);
    
        $now = new \DateTime();
    
        // Calculamos las fechas límite para los 4 y 48 horas
        $date4HoursAgo = (clone $now)->sub(new \DateInterval('PT4H'));
        $date48HoursAgo = (clone $now)->sub(new \DateInterval('PT48H'));
    
        $carritoRepository = $this->entityManager->getRepository(Carrito::class);
    
 
        $carritos4Horas = $carritoRepository->createQueryBuilder('c')
            ->where('c.fecha < :date4HoursAgo')
            ->andWhere('c.fecha >= :date48HoursAgo')
            ->andWhere('c.contador < 1')
            ->setParameter('date4HoursAgo', $date4HoursAgo)
            ->setParameter('date48HoursAgo', $date48HoursAgo)
            ->getQuery()
            ->getResult();
    
   
        $carritos48Horas = $carritoRepository->createQueryBuilder('c')
            ->where('c.fecha < :date48HoursAgo')
            ->andWhere('c.contador < 2')
            ->setParameter('date48HoursAgo', $date48HoursAgo)
            ->getQuery()
            ->getResult();
    
        // Unimos los dos resultados
        $carritos = array_merge($carritos4Horas, $carritos48Horas);

        if (!$carritos){
            $io->warning('No hay carritos para notificar.');
            $this->logCommandOutput('No hay carritos para notificar.', Command::FAILURE);
            return Command::SUCCESS;
        }
    
        foreach ($carritos as $carrito) {
            $productCount = count($carrito->getDetalleCarritos());
    
            // Si el carrito está vacío, lo ignoramos
            if ($productCount === 0) {
                continue;
            }

            $datos=[];
            foreach ($carrito->getDetalleCarritos() as $detalle){

                $s= $detalle->getIdVariacion() ? $detalle->getIdVariacion()->getId() : null;
 
                $imagenesArray=[];
                $terminsoArray=[];
  
  
                if($s != null){
            
                  $variacion = $detalle->getIdVariacion();

                  $precio= $detalle->getIdVariacion()->getPrecio();
                  $precio_rebajado= $detalle->getIdVariacion()->getPrecioRebajado();
    
                if ($variacion->getVariacionesGalerias()->isEmpty()) {
    
                foreach ($detalle->getIdProducto()->getProductosGalerias() as $galeria) {
                    $imagenesArray[] = [
  
                        'url' =>$galeria->getUrlProductoGaleria(),
                    ];
                }
    
                 }else{
           
                foreach ($variacion->getVariacionesGalerias() as $galeria) {
                    $imagenesArray[] = [
                        'url' =>$galeria->getUrlVariacion(),
                    ];
                }
                
               }
    
                  foreach($detalle->getIdVariacion()->getTerminos() as $termino){
    
                      $terminsoArray[]=[
                        'nombre'=>$termino->getNombre()
                      ];
                  }
        
                }else{
            
                  foreach($detalle->getIdProducto()->getProductosGalerias() as $galeria ){
                    $imagenesArray[]=[
                       'url'=>$galeria->getUrlProductoGaleria()            
                    ];
                  }
                  
                }

                $tiene_iva= $detalle->getIdProducto()->isTieneIva();
                $incluye_impuesos=$detalle->getIdProducto()->isImpuestosIncluidos();
                $precio=$detalle->getIdProducto()->getPrecioNormalProducto();
                $precio_rebajado= $detalle->getIdProducto()->getPrecioRebajadoProducto();

                $precioAUsar = ($precio_rebajado !== null && $precio_rebajado !== 0) ? $precio_rebajado : $precio;
                $precio_original = $precioAUsar * $detalle->getCantidad();
                $precio_con_descuento= $precio_original;

                if ($tiene_iva && !$incluye_impuesos && $precio_con_descuento) {
                    // Producto con IVA, pero no incluido en el precio
                    $ivaProducto = ($precio_con_descuento * $iva) / 100; // Calcular el IVA
                    $precio_final = $ivaProducto + $precio_con_descuento; // Precio final con IVA
                
                    $total = $precio_final; // Total incluye el IVA
                    $subtotal = $precio_con_descuento; // Subtotal sin IVA
                } elseif ($tiene_iva && $incluye_impuesos) {
                    // Producto con IVA incluido en el precio
                    $subtotal = ($precio_con_descuento * 100) / (100 + $iva); // Subtotal sin IVA
                    $ivaProducto = $precio_con_descuento - $subtotal; // Calcular el IVA a partir del precio con IVA
                
                    $total = $precio_con_descuento; // Total ya incluye el IVA
                } else {
                    // Producto sin IVA
                    $ivaProducto = 0; // No hay IVA
                    $precio_final = $precio_con_descuento; // Precio final es igual al precio con descuento
                
                    $total = $precio_final; // Total sin cambios
                    $subtotal = $precio_final; // Subtotal sin cambios
                }

                 
            
              $datos[]=[
              'nombre_producto'=>$detalle->getIdProducto()->getNombreProducto(),
              'cantidad'=>$detalle->getCantidad(),
              'subtotal'=>round($subtotal,2),
              'iva'=>round($ivaProducto,2),
              'total'=>round($total,2),
              'tipo_entrega'=>$detalle->getIdProducto()->getEntrgasTipo()->getTipo(),
              'tipo_producto'=>$detalle->getIdProducto()->getProductosTipo() ? $detalle->getIdProducto()->getProductosTipo()->getTipo():'',
              'terminos'=>$terminsoArray,
              'imagenes'=>$imagenesArray
              ];

           }
    
            // Determinar el mensaje basado en el contador
            $subject = 'Tu carrito tiene productos. ¿Quieres continuar con la compra?';
            $template = 'carrito/notificacion_carrito.html.twig';
    
            // Enviamos el correo
            $this->sendEmail($carrito, $subject, $template,$datos);
    
            // Incrementamos el contador y guardamos
            $carrito->setContador($carrito->getContador() + 1);
            $this->entityManager->flush();
        }
    
        $this->logCommandOutput('Notificaciones enviadas con exito', Command::SUCCESS);
        $io->success('Notificaciones enviadas con éxito.');
    
        return Command::SUCCESS;
    }

    private function sendEmail(Carrito $carrito, string $subject, string $template, array $datos)
    {
        $email = (new TemplatedEmail())
            ->to($carrito->getLogin()->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'nombre' => $carrito->getLogin()->getUsuarios()->getNombre() . ' ' . $carrito->getLogin()->getUsuarios()->getApellido(),
                'detalle'=>$datos 
            ]);

        $this->mailer->send($email);
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
