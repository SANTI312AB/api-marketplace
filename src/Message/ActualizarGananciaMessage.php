<?php
namespace App\Message;

class ActualizarGananciaMessage
{
    public int $loginId;
    public int $pedidoId;

    public ?int $cuponId;

    public function __construct(int $loginId, int $pedidoId, ?int $cuponId)
    {
        $this->loginId = $loginId;
        $this->pedidoId = $pedidoId;
        $this->cuponId = $cuponId;
    }

    public function getLoginId(): int
    {
        return $this->loginId;
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getCuponId(): ?int
    {
        return $this->cuponId;
    }
}
