<?php

namespace App\Repository;

use App\Entity\GaleriaTienda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GaleriaTienda>
 *
 * @method GaleriaTienda|null find($id, $lockMode = null, $lockVersion = null)
 * @method GaleriaTienda|null findOneBy(array $criteria, array $orderBy = null)
 * @method GaleriaTienda[]    findAll()
 * @method GaleriaTienda[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GaleriaTiendaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GaleriaTienda::class);
    }

//    /**
//     * @return GaleriaTienda[] Returns an array of GaleriaTienda objects
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

//    public function findOneBySomeField($value): ?GaleriaTienda
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
