<?php

namespace App\Repository;

use App\Entity\ProductosVentas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosVentas>
 *
 * @method ProductosVentas|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosVentas|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosVentas[]    findAll()
 * @method ProductosVentas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosVentasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosVentas::class);
    }

//    /**
//     * @return ProductosVentas[] Returns an array of ProductosVentas objects
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

//    public function findOneBySomeField($value): ?ProductosVentas
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
