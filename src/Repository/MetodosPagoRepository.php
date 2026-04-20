<?php

namespace App\Repository;

use App\Entity\MetodosPago;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetodosPago>
 *
 * @method MetodosPago|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetodosPago|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetodosPago[]    findAll()
 * @method MetodosPago[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetodosPagoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetodosPago::class);
    }

//    /**
//     * @return MetodosPago[] Returns an array of MetodosPago objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MetodosPago
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
