<?php

namespace App\Repository;

use App\Entity\ProductosTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosTipo>
 *
 * @method ProductosTipo|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosTipo|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosTipo[]    findAll()
 * @method ProductosTipo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosTipoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosTipo::class);
    }

//    /**
//     * @return ProductosTipo[] Returns an array of ProductosTipo objects
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

//    public function findOneBySomeField($value): ?ProductosTipo
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
