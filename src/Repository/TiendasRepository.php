<?php

namespace App\Repository;

use App\Entity\Tiendas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tiendas>
 *
 * @method Tiendas|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tiendas|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tiendas[]    findAll()
 * @method Tiendas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TiendasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tiendas::class);
    }

//    /**
//     * @return Tiendas[] Returns an array of Tiendas objects
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

//    public function findOneBySomeField($value): ?Tiendas
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


 public function all_tiendas(){
    
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
    ->select('t')
    ->from('App:tiendas', 't')
    ->where('t.visible = :value')
    ->andWhere('t.id NOT IN (:ids_tienda)')
    ->andWhere('t.estado = :estado')
    ->setParameter('ids_tienda', [685, 630,722,721,720,719,718])
    ->setParameter('value', true)
    ->setParameter('estado', 3)
    ->orderBy('t.nombre_tienda', 'ASC')
 
    ;
    
    return $query->getQuery()->getResult();

 }


 public function tiendas_con_productos()
 {
     $query = $this->getEntityManager()->createQueryBuilder();
     $query->select('t.nombre_tienda as nombre', 't.slug', 'COUNT(DISTINCT p.id) AS total_productos')
         ->from('App:tiendas', 't')
         ->innerJoin('t.productos', 'p')
         ->where('p.disponibilidad_producto = :available')
         ->andWhere('p.suspendido = :notSuspended')
         ->andWhere('p.borrador = :notDraft')
         ->andWhere('p.productos_tipo != :excludedType')
         ->andWhere('t.estado = :excluded')
         ->andWhere('t.visible = :visible')
         ->groupBy('t.nombre_tienda, t.slug')
         ->orderBy('total_productos', 'DESC') // Ordenar por cantidad de productos en orden descendente
         ->addOrderBy('t.nombre_tienda', 'ASC') // Si hay empate, ordenar alfabéticamente por nombre
         ->setParameters([
             'available' => true,
             'notSuspended' => false,
             'notDraft' => false,
             'excludedType' => 3,
             'excluded' => 3,
             'visible' => true
         ]);
 
     return $query->getQuery()->getResult();
 }

}
