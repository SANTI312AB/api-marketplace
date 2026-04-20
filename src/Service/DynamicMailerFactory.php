<?php
namespace App\Service;

use App\Mailer\DynamicDbTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class DynamicMailerFactory
{
    private MailerInterface $mailer;

    public function __construct(
        private DynamicDbTransport $dynamicDbTransport,
        private EventDispatcherInterface $dispatcher,
        private BodyRendererInterface $bodyRenderer,

    ) {

    }

    public function getMailer(): MailerInterface
    {
        if (isset($this->mailer)) {
            return $this->mailer;
        }

        $transport = $this->dynamicDbTransport->getTransport();
        $this->mailer = new Mailer($transport, null, $this->dispatcher);

        return $this->mailer;
    }

    public function send(TemplatedEmail $email): void
    {
        // Establece el remitente por defecto si no se ha configurado
        if (empty($email->getFrom())) {
            $email->from(new Address($this->dynamicDbTransport->defaultFrom(), $this->dynamicDbTransport->default_name()));
        }

        $this->bodyRenderer->render($email);
        $this->getMailer()->send($email);
    }
}