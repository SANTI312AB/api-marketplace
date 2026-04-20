<?php

namespace App\Repository;

use App\Entity\ProductosGaleria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosGaleria>
 *
 * @method ProductosGaleria|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosGaleria|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosGaleria[]    findAll()
 * @method ProductosGaleria[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosGaleriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosGaleria::class);
    }

//    /**
//     * @return ProductosGaleria[] Returns an array of ProductosGaleria objects
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

//    public function findOneBySomeField($value): ?ProductosGaleria
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
