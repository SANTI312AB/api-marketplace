<?php

namespace App\Repository;

use App\Entity\Ganancia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ganancia>
 *
 * @method Ganancia|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ganancia|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ganancia[]    findAll()
 * @method Ganancia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GananciaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ganancia::class);
    }

//    /**
//     * @return Ganancia[] Returns an array of Ganancia objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ganancia
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
