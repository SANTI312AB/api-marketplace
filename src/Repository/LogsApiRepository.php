<?php

namespace App\Repository;

use App\Entity\LogsApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogsApi>
 *
 * @method LogsApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogsApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogsApi[]    findAll()
 * @method LogsApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogsApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogsApi::class);
    }

//    /**
//     * @return LogsApi[] Returns an array of LogsApi objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LogsApi
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
