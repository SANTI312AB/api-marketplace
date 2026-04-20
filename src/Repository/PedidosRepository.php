<?php

namespace App\Repository;

use App\Entity\Pedidos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedidos>
 *
 * @method Pedidos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pedidos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pedidos[]    findAll()
 * @method Pedidos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PedidosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedidos::class);
    }

//    /**
//     * @return Pedidos[] Returns an array of Pedidos objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Pedidos
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function pedidos_filter_vendedor($vendedor,$estado= null,$searchTerm = null,$filters = null)
    {

        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p')
            ->from('App:pedidos', 'p')
            ->innerJoin('p.tienda','t')
            ->innerJoin('p.estado_envio','e')
            ->innerJoin('p.estado_retiro','r')
            ->where('t.id = :vendedor')
            ->setParameter('vendedor',$vendedor)
            ->orderBy('p.fecha_pedido','DESC');


        if($searchTerm !== null){
            $query->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('LOWER(p.numero_pedido)', ':searchTerm'),
                    $query->expr()->like('LOWER(p.n_venta)', ':searchTerm'),
                    $query->expr()->like('LOWER(p.customer)', ':searchTerm')
                )
            )
            ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($estado !== null) {
            $query->andWhere('p.estado = :estado')
            ->setParameter('estado', $estado);
        }


        $orderBy = $filters['orderBy'] ?? null;

        if ($orderBy !== null) {
            // Verifica si $orderBy contiene '_'
            if (strpos($orderBy, '_') !== false) {
                // Si contiene '_', divide la cadena en campo y dirección
                list($campo, $direccion) = explode('_', $orderBy);
            } else {
                // Si no contiene '_', usa $orderBy como campo y establece un valor predeterminado para dirección
                $campo = $orderBy;
                $direccion = null; // o 'desc', según lo que desees como predeterminado
            }
        
            // Luego, procede con tu lógica de ordenamiento
            switch ($campo) {
                case 'fecha':
                    $query->orderBy('p.fecha_pedido', $direccion);
                    break;
    
            }
        }


        return $query->getQuery()->getResult(); 

    }


    public function pedidos_filter_cliente($cliente,$estado= null,$searchTerm = null,$filters = null)
    {

        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p')
            ->from('App:pedidos', 'p')
            ->innerJoin('p.login','t')
            ->innerJoin('p.estado_envio','e')
            ->innerJoin('p.estado_retiro','r')
            ->where('t.id = :cliente')
            ->setParameter('cliente',$cliente)
            ->orderBy('p.fecha_pedido','DESC');


        if($searchTerm !== null){
            $query->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('LOWER(p.numero_pedido)', ':searchTerm'),
                    $query->expr()->like('LOWER(p.n_venta)', ':searchTerm'),
                )
            )
            ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        if ($estado !== null) {
            $query->andWhere('p.estado = :estado')
            ->setParameter('estado', $estado);
        }


        $orderBy = $filters['orderBy'] ?? null;

        if ($orderBy !== null) {
            // Verifica si $orderBy contiene '_'
            if (strpos($orderBy, '_') !== false) {
                // Si contiene '_', divide la cadena en campo y dirección
                list($campo, $direccion) = explode('_', $orderBy);
            } else {
                // Si no contiene '_', usa $orderBy como campo y establece un valor predeterminado para dirección
                $campo = $orderBy;
                $direccion = null; // o 'desc', según lo que desees como predeterminado
            }
        
            // Luego, procede con tu lógica de ordenamiento
            switch ($campo) {
                case 'fecha':
                    $query->orderBy('p.fecha_pedido', $direccion);
                    break;
    
            }
        }


        return $query->getQuery()->getResult(); 

    }

      



    public function pediddos_pendientes($pedido){
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('p')
            ->from('App:pedidos', 'p')
            ->where('p.estado = :value')
            ->andWhere('p.metodo_pago = 1 OR p.metodo_pago = 3')
            ->andWhere('p.numero_pedido = :pedidos ')
            ->setParameter('value','PENDING')
            ->setParameter('pedidos', $pedido)
            ->setMaxResults(1);
            return $query->getQuery()->getOneOrNullResult();  
    }


    public function findPedidosPendientesConPrefijo($user, $metodo_pago)
    {
        return $this->createQueryBuilder('p')
            ->where('p.login = :user')
            ->andWhere('p.estado = :estado')
            ->andWhere('p.metodo_pago = :metodo_pago')
            ->setParameter('user', $user)
            ->setParameter('estado', 'PENDING')
            ->setParameter('metodo_pago', $metodo_pago)
            ->getQuery()
            ->getResult();
    }

    public function pedidos_cupon_referidos($tienda){
        
        $query = $this->getEntityManager()->createQueryBuilder();

        $query->select('d')
            ->from('App:pedidos', 'd')
            ->innerJoin('d.cupon','c')
            ->where('c.tienda = :val')
            ->andWhere('d.estado = :val4')
            ->andWhere('c.add_saldo = :val5')
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
                'val5' => true,
                'domicilio' => 'A DOMICILIO',
                'retiro' => 'RETIRO EN TIENDA FISICA',
                'ambos' => 'AMBOS',
                'value' => ''
            ]);

        return $query->getQuery()->getResult();

    }

    public function pedidos_por_facturar()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d')
            ->from('App:pedidos', 'd')
            ->where('d.estado = :estado')
            ->andWhere('d.provincia <> :empty')
            ->andWhere('d.region <> :empty')
            ->andWhere('d.subtotal <> :empty')
            ->andWhere('d.iva <> :empty')
            ->andWhere('d.total <> :empty')
            ->andWhere('d.total_final <> :empty')
            // filtramos por metodo_pago 1 y 4 usando parámetro array
            ->andWhere($qb->expr()->in('d.metodo_pago', ':methods'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('d.clave_facturador'),
                    $qb->expr()->eq('d.clave_facturador', ':empty')
                )
            )
            ->setParameter('estado', 'APPROVED')
            // importante: pasar tipo PARAM_INT_ARRAY para arrays de enteros
            ->setParameter('methods', [1, 4], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
            ->setParameter('empty', '');

        return $qb->getQuery()->getResult();
    }

}
