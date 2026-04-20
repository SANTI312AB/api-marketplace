<?php
namespace App\Mailer;

use App\Entity\GeneralesApp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class DynamicDbTransport
{
    private TransportInterface $transport;

    private array $config=[];

    public function __construct(EntityManagerInterface $em)
    {
        $repo = $em->getRepository(GeneralesApp::class);
        $params = $repo->findBy(['nombre' => 'gmail']);

        $this->config = [];
        foreach ($params as $param) {
            $this->config[$param->getAtributoGeneral()] = $param->getValorGeneral();
        }

        // Verificar que las credenciales estén completas
        if (empty($this->config['Login']) || empty($this->config['SecretKey']) || empty($this->config['smtpEncryption'])) {
            throw new \Exception('Faltan credenciales Gmail o los valores son incorrectos: ' . json_encode($this->config));
        }

        // Construir el DSN dinámicamente
        $dsn = sprintf(
            $this->config['smtpEncryption'].'://%s:%s@default',
            urlencode(trim($this->config['Login'])),
            urlencode(trim($this->config['SecretKey']))
        );

        try {
            // Crear el transporte usando el DSN
            $this->transport = Transport::fromDsn($dsn);
        } catch (TransportExceptionInterface $e) {
            throw new \Exception('Error al crear el transporte: ' . $e->getMessage());
        }
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    public function defaultFrom(): ?string
    {
        return $this->config['Login'] ?? null;
    }

    public function default_name(): ?string
    {
        return $this->config['Username'] ?? null;
    }

}