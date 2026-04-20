<?php   

namespace App\Service;

use App\Entity\DetallePedido;
use App\Entity\Ganancia;
use App\Entity\Login;
use App\Entity\Retiros;
use App\Entity\Tiendas;
use Doctrine\ORM\EntityManagerInterface;


class GestionarTransacciones{

    private $em;

    public function __construct(EntityManagerInterface $em){

        $this->em= $em;
    }
    public function calcularTransacciones(Login $user)
    {
        $tienda = $this->em->getRepository(Tiendas::class)->findOneBy(['login' => $user]);
        if (!$tienda) {
            throw new \Exception('Tienda no encontrada para el usuario.');
        }

        $pedidos = $this->em->getRepository(DetallePedido::class)->filter_transactions($tienda);

        $gananciasVendedor = $this->em->getRepository(Ganancia::class)->findOneBy(['login' => $user]);
        if (!$gananciasVendedor) {
            $gananciasVendedor = new Ganancia();
            $gananciasVendedor->setLogin($user);
            $this->em->persist($gananciasVendedor);
        }

        $comision = $tienda->getComision();


        $numeroVentas = 0;
        $total_general = 0;
        $pedidosArray = [];

        foreach ($pedidos as $pedido) {
            $cantidadTotal = 0;
            foreach ($pedido->getDetallePedidos() as $detalle) {
                $cantidadTotal += $detalle->getCantidad();
            }

            $pedidosArray[] = [
                'numero_orden' => $pedido->getNumeroPedido(),
                'tipo_envio'   => $pedido->getTipoEnvio(),
                'estado'       => $pedido->getEstado(),
                'fecha'        => $pedido->getFechaPedido(),
                'total'        => $pedido->getTotal(),
                'items'        => $cantidadTotal,
            ];

            $numeroVentas++;
            $total_general += $pedido->getTotal();
        }


        $retiros_aprobados = $this->em->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => ['APPROVED']]);
        $movido_saldo = $this->em->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => ['MOVIDO_SALDO']]);
        $retiros_pendientes = $this->em->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'PENDING']);
        $retiros_rechazados = $this->em->getRepository(Retiros::class)->findBy(['ganancia' => $gananciasVendedor, 'estado' => 'REJECTED']);

        // Totales de retiros aprobados
        $total_retiros_aprobados = 0;
        $total_retiros_aprobados_final = 0;
        $total_comision_aprobados = 0;
        foreach ($retiros_aprobados as $retiro) {
            $total_retiros_aprobados += $retiro->getRetiro();
            $total_comision_aprobados += $retiro->getComisionShopby();
            $total_retiros_aprobados_final += $retiro->getRetiroFinal();
        }

        // total movidos a saldo
        $total_movidos_saldo = 0;
        $total_movidos_saldo_final = 0;
        $total_comision_movidos_saldo = 0;
        foreach ($movido_saldo as $saldo) {
            $total_movidos_saldo += $saldo->getRetiro();
            $total_movidos_saldo_final += $saldo->getComisionShopby();
            $total_comision_movidos_saldo += $saldo->getRetiroFinal();
        }

        // Totales de retiros pendientes
        $total_retiros_pendientes = 0;
        $total_retiros_pendientes_final = 0;
        $total_comision_pendientes = 0;
        foreach ($retiros_pendientes as $retiro2) {
            $total_retiros_pendientes += $retiro2->getRetiro();
            $total_retiros_pendientes_final += $retiro2->getRetiroFinal();
            $total_comision_pendientes += $retiro2->getComisionShopby();
        }

        // Cálculos de saldo disponible y ganancia
        $calculo_comision = round(($gananciasVendedor->getDisponible() * $comision) / 100, 2, PHP_ROUND_HALF_UP);
        $total_recibir_disponible = round($gananciasVendedor->getDisponible() - $calculo_comision, 2, PHP_ROUND_HALF_UP);
        $saldo_disponible = [
            'subtotal' => $gananciasVendedor->getDisponible(),
            'comision_shopby' => $calculo_comision,
            'total' => $total_recibir_disponible
        ];

        $valores_pendientes = [
            'subtotal' => $total_retiros_pendientes,
            'comision_shopby' => $total_comision_pendientes,
            'total' => $total_retiros_pendientes_final
        ];

        $valores_aprobados = [
            'subtotal' => $total_retiros_aprobados,
            'comision_shopby' => $total_comision_aprobados,
            'total' => $total_retiros_aprobados_final
        ];

        $movido_saldo_data=[
            'subtotal' => $total_movidos_saldo,
            'comision_shopby' => $total_comision_movidos_saldo,
            'total' => $total_movidos_saldo_final
        ];

        $calculo_comision_ganancia = round($gananciasVendedor->getGanacia() * $comision / 100, 2, PHP_ROUND_HALF_UP);
        $total_recibir_ganancia = round($gananciasVendedor->getGanacia() - $calculo_comision_ganancia, 2, PHP_ROUND_HALF_UP);
        $ganancia_vendedor_data = [
            'subtotal' => $gananciasVendedor->getGanacia(),
            'comision_shopby' => $calculo_comision_ganancia,
            'total' => $total_recibir_ganancia
        ];

        if (!empty($retiros_rechazados)) {
            $gananciasVendedor->setDisponible($total_general);
        }
        if ($total_retiros_aprobados > 0) {
            $gananciasVendedor->setDisponible($gananciasVendedor->getGanacia() - $total_retiros_aprobados);
            $gananciasVendedor->setTotalRetiros($total_retiros_aprobados);
            $gananciasVendedor->setTotalComision($total_comision_aprobados);
            $gananciasVendedor->setTotalRecibir($total_retiros_aprobados_final);
        }

        if ($total_movidos_saldo > 0) {
            $gananciasVendedor->setDisponible($gananciasVendedor->getGanacia() - $total_movidos_saldo);
            $gananciasVendedor->setTotalRetiros($total_movidos_saldo);
            $gananciasVendedor->setTotalComision($total_comision_movidos_saldo);
            $gananciasVendedor->setTotalRecibir($total_movidos_saldo_final);
        }


        /*if ($total_retiros_pendientes > 0) {
            $gananciasVendedor->setDisponible(0);
        }*/

        $this->em->flush();
    }

}