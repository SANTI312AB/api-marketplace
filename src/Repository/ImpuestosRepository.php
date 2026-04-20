<?php

namespace App\Repository;

use App\Entity\Impuestos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Impuestos>
 *
 * @method Impuestos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Impuestos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Impuestos[]    findAll()
 * @method Impuestos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImpuestosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Impuestos::class);
    }

//    /**
//     * @return Impuestos[] Returns an array of Impuestos objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Impuestos
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
