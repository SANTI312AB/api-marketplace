<?php

namespace App\Repository;

use App\Entity\Estados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Estados>
 *
 * @method Estados|null find($id, $lockMode = null, $lockVersion = null)
 * @method Estados|null findOneBy(array $criteria, array $orderBy = null)
 * @method Estados[]    findAll()
 * @method Estados[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Estados::class);
    }

//    /**
//     * @return Estados[] Returns an array of Estados objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Estados
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

      public function productos_con_estado(){
          
        return $this->createQueryBuilder('e')
        ->select('e.nobre_estado AS nombre', 'COUNT(p.id) AS total_productos')
        ->join('e.productos','p')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product', 3)
        ->groupBy('e.id')  
        ->addGroupBy('e.nobre_estado') 
        ->orderBy('COUNT(e.id)', 'DESC')  // Ordenar por el número de productos en orden descendente
        ->getQuery()
        ->getResult();

      }
}
