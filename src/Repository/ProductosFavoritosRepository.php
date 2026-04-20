<?php

namespace App\Repository;

use App\Entity\ProductosFavoritos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosFavoritos>
 *
 * @method ProductosFavoritos|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosFavoritos|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosFavoritos[]    findAll()
 * @method ProductosFavoritos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosFavoritosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosFavoritos::class);
    }

//    /**
//     * @return ProductosFavoritos[] Returns an array of ProductosFavoritos objects
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

//    public function findOneBySomeField($value): ?ProductosFavoritos
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
