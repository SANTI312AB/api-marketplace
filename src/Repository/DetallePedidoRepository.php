<?php

namespace App\Repository;

use App\Entity\DetallePedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetallePedido>
 *
 * @method DetallePedido|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetallePedido|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetallePedido[]    findAll()
 * @method DetallePedido[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetallePedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetallePedido::class);
    }




    public function full_pedidos($tienda)
    {

        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('d')
            ->from('App:detallepedido', 'd')
            ->innerJoin('d.pedido', 'p')
            ->andWhere('d.tienda = :val2')
            ->setParameter('val2', $tienda)
            ->orderBy('p.fecha_pedido', 'DESC')
        ;

        return $query->getQuery()->getResult();

    }

    public function filter_transactions($tienda)
    {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query->select('d')
            ->from('App:pedidos', 'd')
            ->where('d.tienda = :val')
            ->andWhere('d.estado = :val4')

            // Apply the orX and andX logic for envio conditions
            ->andWhere(
                $query->expr()->orX(
                    $query->expr()->andX(
                        'd.tipo_envio = :domicilio',
                        'd.estado_envio = 22'
                    ),
                    $query->expr()->andX(
                        'd.tipo_envio = :retiro',
                        'd.estado_retiro = 22'
                    ),
                    $query->expr()->andX(
                        'd.tipo_envio = :ambos',
                        'd.estado_envio = 22',
                        'd.estado_retiro = 22'
                    )
                )
            )

            ->andWhere('d.provincia <> :value')
            ->andWhere('d.region <> :value')
            ->andWhere('d.subtotal <> :value')
            ->andWhere('d.iva <> :value')
            ->andWhere('d.total <> :value')
            ->andWhere('d.total_final <> :value')

            // Set the necessary parameters for the query
            ->setParameters([
                'val' => $tienda,
                'val4' => 'APPROVED',
                'domicilio' => 'A DOMICILIO',
                'retiro' => 'RETIRO EN TIENDA FISICA',
                'ambos' => 'AMBOS',
                'value' => ''
            ]);

        return $query->getQuery()->getResult();
    }

    
    public function servi_guias($pedido, $tienda)
    {

        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('d')
            ->from('App:detallepedido', 'd')
            ->innerJoin('d.pedido', 'pd')
            ->innerJoin('d.IdProductos', 'p')
            ->Where('pd.metodo_envio = :val')
            ->andWhere('pd.numero_pedido = :val2')
            ->andWhere('pd.tienda = :val3')
            ->andWhere('pd.estado = :val4')
            ->setParameter('val', 1)
            ->setParameter('val2', $pedido)
            ->setParameter('val3', $tienda)
            ->setParameter('val4', 'APPROVED')
        ;

        return $query->getQuery()->getResult();
    }

    public function costo_envio_pedido($pedido)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select(
            'MAX(d.ciudad_remite) AS ciudad',
            'MAX(d.provincia) AS provincia',
            'MAX(d.region) AS region',
            'SUM(d.cantidad) AS total_cantidad',
            'SUM(d.peso) AS total_peso',
            'SUM(d.subtotal) AS total_precio',
        )
            ->from('App:detallepedido', 'd')
            ->innerJoin('d.pedido', 'pd')
            ->innerJoin('d.IdProductos', 'p')
            ->where('pd.id = :id')
            ->andWhere('p.entrgas_tipo = 1')
            ->groupBy('pd.id')
        ;

        $query->setParameter('id', $pedido);

        return $query->getQuery()->getResult();
    }


    public function costo_envio_venta($user)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select(
            'MAX(d.ciudad_remite) AS ciudad',
            'MAX(d.provincia) AS provincia',
            'MAX(d.region) AS region',
            'SUM(d.cantidad) AS total_cantidad',
            'SUM(d.peso) AS total_peso',
            'SUM(d.subtotal) AS total_precio',
        )
            ->from('App:detallepedido', 'd')
            ->innerJoin('d.IdProductos', 'p')
            ->innerJoin('d.pedido', 'pd')
            ->where('pd.login = :user')
            ->andWhere('p.entrgas_tipo = 1')
            ->groupBy('pd.id')
        ;

        $query->setParameter('user', $user);

        return $query->getQuery()->getResult();
    }



    public function n_ventas_producto($producto)
    {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query->select('d')
            ->from('App:pedidos', 'd')
            ->innerJoin('d.detallePedidos', 'dp')
            ->where('dp.IdProductos = :val')
            ->andwhere('d.estado = :estadoPago')
            ->andWhere(
                $query->expr()->orX(
                    $query->expr()->andX(
                        'd.tipo_envio = :domicilio',
                        'd.estado_envio = 22'
                    ),
                    $query->expr()->andX(
                        'd.tipo_envio = :retiro',
                        'd.estado_retiro = 22'
                    ),
                    $query->expr()->andX(
                        'd.tipo_envio = :ambos',
                        'd.estado_envio = 22',
                        'd.estado_retiro = 22'
                    )
                )
            )
            ->andWhere('d.provincia <> :value')
            ->andWhere('d.region <> :value')
            ->andWhere('d.subtotal <> :value')
            ->andWhere('d.iva <> :value')
            ->andWhere('d.total <> :value')
            ->andWhere('d.total_final <> :value ')
            ->setParameters([
                'estadoPago' => 'APPROVED',
                'domicilio' => 'A DOMICILIO',
                'retiro' => 'RETIRO EN TIENDA FISICA',
                'ambos' => 'AMBOS',
                'value' => '',

            ])
            ->setParameter('val', $producto)

        ;
        return $query->getQuery()->getResult();
    }


    public function servicio_reservado($producto, $login)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('d')
            ->from('App:detallepedido', 'd')
            ->innerJoin('d.pedido', 'pd')
            ->innerJoin('d.IdProductos', 'p')
            ->where('p.id = :val')
            ->andWhere('pd.login = :val2')
            ->andWhere('pd.estado = :val3')
            ->andWhere('d.fecha_fin_servicio >= CURRENT_DATE()') // Only check end date
            ->setParameter('val', $producto)
            ->setParameter('val2', $login)
            ->setParameter('val3', 'APPROVED') // Ensure correct estado value
            ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

}
