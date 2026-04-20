<?php

namespace App\Repository;

use App\Entity\Terminos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Terminos>
 *
 * @method Terminos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Terminos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Terminos[]    findAll()
 * @method Terminos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TerminosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Terminos::class);
    }

//    /**
//     * @return Terminos[] Returns an array of Terminos objects
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

//    public function findOneBySomeField($value): ?Terminos
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
