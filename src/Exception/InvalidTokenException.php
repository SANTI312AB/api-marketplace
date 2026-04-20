<?php
namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidTokenException extends AuthenticationException
{
    private $reason;

    public function __construct(string $reason, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}