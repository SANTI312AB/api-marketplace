<?php

namespace App\Command;

use App\Entity\FuncionesEspeciales;
use App\Entity\Servientrega;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\CommandLog;
// ✅ AÑADE ESTA LÍNEA
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:excel_servientrega',
    description: 'Genera Excel de guías Servientrega'
)]
class ExcelCommand extends Command
{

    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private ParameterBagInterface $parameters;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer, ParameterBagInterface $parameters)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->parameters = $parameters;
    }

    protected function configure(): void
    {
        $this->setDescription('Genera Excel de guías Servientrega');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email_excel = $this->entityManager->getRepository(FuncionesEspeciales::class)->findOneBy(['id' => 3]);

        
        if (!$email_excel) {
            throw new \Exception('No se encontró la función especial para generar Excel');
        }

        $guias = $this->entityManager->getRepository(Servientrega::class)->excel_repository();


        if (!$guias) {  
            $io = new SymfonyStyle($input, $output);
            $this->logCommandOutput('No hay guias servientrega para procesar', Command::FAILURE);
            $io->warning('No hay guias servientrega para procesar');
            return Command::FAILURE;
        }


        

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Nombre de tu aplicación")
            ->setTitle("Datos exportados");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nro Pedido')
              ->setCellValue('B1', 'Nro Guia')
              ->setCellValue('C1', 'Fecha y Hora del pedido')
              ->setCellValue('D1', 'Dirección de Origen')
              ->setCellValue('E1', 'Nombre Contacto de origen')
              ->setCellValue('F1', 'Teléfono de contacto de origen')
              ->setCellValue('G1', 'Descripción pedido')
              ->setCellValue('H1', 'Cantidad de Artículos')
              ->setCellValue('I1', 'Peso total (Kg)')
              ->setCellValue('J1', 'Tienda')
              ->setCellValue('K1', 'Observación');

        $row = 2;
        foreach ($guias as $guia) {
            $dato= null;
            $nombre = $guia->getNombreVendedor().' '.$guia->getApellidoVendedor();
            $contacto = $guia->getTienda() ? $guia->getTienda()->getNombreContacto():null;
            if($contacto){
               $dato= $contacto;
            }else{
                $dato=$nombre;
            }
            $guia->setExcelGenerado(true);
            $this->entityManager->flush();
            $sheet->setCellValue('A'.$row, $guia->getNPedido())
                  ->setCellValue('B'.$row, $guia->getCodigoServientrega())
                  ->setCellValue('C'.$row, $guia->getFechaPedido())
                  ->setCellValue('D'.$row, $guia->getDireccionRemite())
                  ->setCellValue('E'.$row, $dato)
                  ->setCellValue('F'.$row, $guia->getCelularVendedor())
                  ->setCellValue('G'.$row, $guia->getProductos())
                  ->setCellValue('H'.$row, $guia->getCantidadTotal())
                  ->setCellValue('I'.$row, $guia->getPesoTotal())
                  ->setCellValue('J'.$row, $guia->getTienda() ? $guia->getTienda()->getNombreContacto() : '')
                  ->setCellValue('K'.$row, $guia->getObservacion());
            $row++;
        }

        // Aplicar formatos a las celdas
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:K'.$row)->applyFromArray($styleArray);

        $sheet->getStyle('A1:K'.$row)->getAlignment()
              ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
              ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle('I2:K'.$row)->getNumberFormat()
              ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // Ajustar el ancho de las columnas automáticamente según el contenido
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Aplicar un formato de texto envolvente para que el texto se ajuste automáticamente al ancho de la celda
        $sheet->getStyle('A1:K'.$row)->getAlignment()->setWrapText(true);

        $filename = 'servientrega_guias_shopby_' . date('Y-m-d_H-i-s') . '.xlsx';
        $directory = $this->parameters->get('excel_files'); // Asegúrate de que esta ruta sea correcta
        $filePath = $directory . '/' . $filename;
        
        // Verificar existencia y permisos del directorio
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $emails = array_map('trim', explode(',', $email_excel->getDescripcion()));

        $email = (new Email())
            ->from(new Address($this->parameters->get('email_user'), 'Shopby'))
            ->to(...$emails)
            ->subject('Listado de pedidos a retirar ' . date('Y-m-d_H:i:s'))
            ->html('<p>Hola, se ha generado el siguiente listado de pedidos para su recogida, se anexa en el documento Excel.
                    Este correo se genera de forma automática, por favor no responder, para cualquier duda, error o consulta escribir a soporte@shopby.com.ec</p>')
            ->attachFromPath($filePath);

        $this->mailer->send($email);

        $io = new SymfonyStyle($input, $output);
            $this->logCommandOutput('Archivo Excel generado en: ' . $filePath, Command::SUCCESS);
            $io->success('Archivo Excel generado en: ' . $filePath);

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
