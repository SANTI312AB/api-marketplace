<?php

namespace App\Repository;

use App\Entity\TarifasServientrega;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifasServientrega>
 *
 * @method TarifasServientrega|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifasServientrega|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifasServientrega[]    findAll()
 * @method TarifasServientrega[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifasServientregaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifasServientrega::class);
    }

//    /**
//     * @return TarifasServientrega[] Returns an array of TarifasServientrega objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TarifasServientrega
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
