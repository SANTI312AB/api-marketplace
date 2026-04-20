<?php

namespace App\Repository;

use App\Entity\Variaciones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Variaciones>
 *
 * @method Variaciones|null find($id, $lockMode = null, $lockVersion = null)
 * @method Variaciones|null findOneBy(array $criteria, array $orderBy = null)
 * @method Variaciones[]    findAll()
 * @method Variaciones[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariacionesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variaciones::class);
    }

//    /**
//     * @return Variaciones[] Returns an array of Variaciones objects
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

//    public function findOneBySomeField($value): ?Variaciones
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

}
