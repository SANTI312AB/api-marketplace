<?php

namespace App\Repository;

use App\Entity\ProductosComentarios;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosComentarios>
 *
 * @method ProductosComentarios|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosComentarios|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosComentarios[]    findAll()
 * @method ProductosComentarios[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosComentariosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosComentarios::class);
    }

//    /**
//     * @return ProductosComentarios[] Returns an array of ProductosComentarios objects
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

//    public function findOneBySomeField($value): ?ProductosComentarios
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }  



      public function comentarios_tienda($tienda) {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('c') 
        ->from('App:productoscomentarios', 'c')
        ->innerJoin('c.productos','p')
        ->innerJoin('p.tienda','t')
        ->where('t.id = :id')
        ->setParameter('id', $tienda);
        return $query->getQuery()->getResult();
        
      }




}
