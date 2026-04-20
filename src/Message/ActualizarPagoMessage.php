<?php
namespace App\Message;

class ActualizarPagoMessage
{

    public int $pedidoId;

    public function __construct(int $pedidoId)
    {
        $this->pedidoId = $pedidoId;
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

}
