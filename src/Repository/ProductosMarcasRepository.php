<?php

namespace App\Repository;

use App\Entity\ProductosMarcas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductosMarcas>
 *
 * @method ProductosMarcas|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductosMarcas|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductosMarcas[]    findAll()
 * @method ProductosMarcas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductosMarcasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductosMarcas::class);
    }

//    /**
//     * @return ProductosMarcas[] Returns an array of ProductosMarcas objects
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

//    public function findOneBySomeField($value): ?ProductosMarcas
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function findCategoriesWithMarcas($categoria=null)
{
  $query = $this->getEntityManager()->createQueryBuilder();

  $query
      ->select('m')
      ->from('App:productosmarcas', 'm')
      ->innerJoin('m.categorias','c')
      ->where('m.published = :value')
      ->setParameter('value', true)
      ->orderBy('m.nombre_m','ASC')
      ;

      if ($categoria !== null) {

          $query->andWhere('c.slug = :categoria')
              ->setParameter('categoria', $categoria);
      }
  
      return $query->getQuery()->getResult();

}


 
public function findproductos_marcas($categoria = null)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('p')  // Selecting the whole product entity
        ->from('App:productos', 'p')
        ->leftJoin('p.categoriasTiendas', 'c')
        ->groupBy('p.marcas');  // Group by all selected columns

    if ($categoria !== null) {
        $query->andWhere('c.slug = :categoria')
            ->setParameter('categoria', $categoria);
    }

    return $query->getQuery()->getResult();
}


public function marcas_con_producto($categoria = null)
{
    $query = $this->getEntityManager()->createQueryBuilder();

    $query
        ->select('m.nombre_m as nombre', 'm.slug as slug','COUNT(p.id) AS total_productos')  // Selecting the whole product entity
        ->from('App:Productos', 'p')
        ->innerJoin('p.marcas', 'm')
        ->innerJoin('p.categorias', 'c')
        ->where('p.disponibilidad_producto = :value')
        ->andWhere('p.suspendido = :value2')
        ->andWhere('p.borrador = :value3')
        ->andWhere('p.productos_tipo != :tipo_product')
        ->setParameter('value', true)
        ->setParameter('value2', false)
        ->setParameter('value3', false)
        ->setParameter('tipo_product', 3)
        ->GroupBy('m.id') 
        ->orderBy('total_productos', 'DESC')  // Ordenar por el número de productos en orden descendente
        ->getQuery()
        ->getResult();

    if ($categoria !== null) {
        $query->andWhere('c.slug = :categoria')
            ->setParameter('categoria', $categoria);
    }

    return $query->getQuery()->getResult();
}


    

}
