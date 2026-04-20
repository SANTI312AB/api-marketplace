<?php
namespace App\Security;

use App\Entity\Login as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    public function checkPreAuth(UserInterface $user): void
    {

        if (!$user instanceof AppUser ) {
            return;
        }

        if ($user->getEstados()->getId() == 2) {
          
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Tu cuenta está bloqueada.',[], 423);
        }


        
        if ($user->getVericacion()->getId() == 8) {
          
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Revisa tu correo para verificar la cuenta.',[], 403);
        }


    }


    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        if ($user->getEstados()->getId() == 2) {
          
            throw new CustomUserMessageAccountStatusException('Tu cuenta está bloqueada.',[], 423);
         
        }

          
        if ($user->getVericacion()->getId() == 8) {
          
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Revisa tu correo para verificar la cuenta.',[], 403);
        }

    }

}