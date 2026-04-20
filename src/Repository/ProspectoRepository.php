<?php

namespace App\Repository;

use App\Entity\Prospecto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prospecto>
 *
 * @method Prospecto|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prospecto|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prospecto[]    findAll()
 * @method Prospecto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProspectoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prospecto::class);
    }

    //    /**
    //     * @return Prospecto[] Returns an array of Prospecto objects
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

    //    public function findOneBySomeField($value): ?Prospecto
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
