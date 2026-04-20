<?php

namespace App\Repository;

use App\Entity\VariacionesGaleria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VariacionesGaleria>
 *
 * @method VariacionesGaleria|null find($id, $lockMode = null, $lockVersion = null)
 * @method VariacionesGaleria|null findOneBy(array $criteria, array $orderBy = null)
 * @method VariacionesGaleria[]    findAll()
 * @method VariacionesGaleria[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariacionesGaleriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VariacionesGaleria::class);
    }

//    /**
//     * @return VariacionesGaleria[] Returns an array of VariacionesGaleria objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VariacionesGaleria
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
