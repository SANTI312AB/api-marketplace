<?php

namespace App\Repository;

use App\Entity\Provincias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Provincias>
 *
 * @method Provincias|null find($id, $lockMode = null, $lockVersion = null)
 * @method Provincias|null findOneBy(array $criteria, array $orderBy = null)
 * @method Provincias[]    findAll()
 * @method Provincias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProvinciasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Provincias::class);
    }

//    /**
//     * @return Provincias[] Returns an array of Provincias objects
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

//    public function findOneBySomeField($value): ?Provincias
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
