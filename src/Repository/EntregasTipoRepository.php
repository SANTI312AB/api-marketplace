<?php

namespace App\Repository;

use App\Entity\EntregasTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntregasTipo>
 *
 * @method EntregasTipo|null find($id, $lockMode = null, $lockVersion = null)
 * @method EntregasTipo|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntregasTipo[]    findAll()
 * @method EntregasTipo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntregasTipoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntregasTipo::class);
    }

//    /**
//     * @return EntregasTipo[] Returns an array of EntregasTipo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EntregasTipo
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


      public function entregas_con_producto(){

          return $this->createQueryBuilder('et')
          ->select('et.tipo AS nombre','et.slug AS slug', 'COUNT(p.id) AS total_productos')
          ->innerJoin('et.productos', 'p')
          ->where('p.disponibilidad_producto = :value')
          ->andWhere('p.suspendido = :value2')
          ->andWhere('p.borrador = :value3')
          ->andWhere('p.productos_tipo != :tipo_product')
          ->setParameter('value', true)
          ->setParameter('value2', false)
          ->setParameter('value3', false)
          ->setParameter('tipo_product', 3)
          ->groupBy('et.id')
          ->addGroupBy('et.tipo')
          ->orderBy('COUNT(et.id)', 'DESC')
          ->getQuery()
          ->getResult();


      }


}
