<?php

namespace App\Repository;

use App\Entity\CategoriasTienda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoriasTienda>
 *
 * @method CategoriasTienda|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoriasTienda|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoriasTienda[]    findAll()
 * @method CategoriasTienda[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoriasTiendaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoriasTienda::class);
    }

    //    /**
    //     * @return CategoriasTienda[] Returns an array of CategoriasTienda objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CategoriasTienda
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


}
