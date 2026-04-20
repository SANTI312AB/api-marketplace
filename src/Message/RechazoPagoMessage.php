<?php

namespace App\Message;

class RechazoPagoMessage
{
    public function __construct(
        private int $id_user,
        private string $venta
    ) {}

    public function getLogin(): int
    {
        return $this->id_user;
    }

    public function getVenta(): string
    {

        return $this->venta;
    }
}
