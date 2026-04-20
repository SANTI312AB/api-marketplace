<?php

namespace App\Repository;

use App\Entity\Subcategorias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subcategorias>
 *
 * @method Subcategorias|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subcategorias|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subcategorias[]    findAll()
 * @method Subcategorias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubcategoriasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subcategorias::class);
    }

//    /**
//     * @return Subcategorias[] Returns an array of Subcategorias objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Subcategorias
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function SubcategoriascoProductos($categoria=null, $producto_tipo=null){

    $query = $this->getEntityManager()->createQueryBuilder();

    $query
    ->select('s.nombre AS nombre','s.slug AS slug', 'COUNT(p.id) AS total_productos')
    ->from('App:subcategorias', 's')
    ->Join('s.productos', 'p')
    ->join('s.categorias', 'c')
    ->Join('p.productos_tipo', 'pt')
    ->where('p.disponibilidad_producto = :value')
    ->andWhere('p.suspendido = :value2')
    ->andWhere('p.borrador = :value3')
    ->andWhere('p.productos_tipo != :tipo_product')
    ->setParameter('value', true)
    ->setParameter('value2', false)
    ->setParameter('value3', false)
    ->setParameter('tipo_product', 3)
    ->groupBy('s.id')
    ->addGroupBy('s.nombre')
    ->orderBy('COUNT(s.id)', 'DESC');

    if($categoria !== null){
       $query->andWhere('c.slug = :cat')
      ->setParameter('cat', $categoria);
    }

    if($producto_tipo !== null) {
        $query->andWhere('pt.tipo = :filtro_tipo')
            ->setParameter('filtro_tipo', $producto_tipo);
    }

    return $query->getQuery()->getResult();
  }
}
