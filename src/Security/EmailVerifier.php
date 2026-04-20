<?php

namespace App\Security;

use App\Repository\EstadosRepository;
use App\Service\DynamicMailerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private DynamicMailerFactory $mailer;
    private EntityManagerInterface $entityManager;
    private EstadosRepository $estadosRepository;

    public function __construct(VerifyEmailHelperInterface $helper, DynamicMailerFactory $mailer, EntityManagerInterface $manager,EstadosRepository $estadosRepository)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->entityManager = $manager;
        $this->estadosRepository = $estadosRepository;
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, UserInterface $user, TemplatedEmail $email,$state): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId(),'state' => $state]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }


    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
        $estado= $this->estadosRepository->findOneBy(['id'=>7]);

        $user->setVericacion($estado);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}