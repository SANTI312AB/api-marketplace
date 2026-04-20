<?php

namespace App\Repository;

use App\Entity\DetalleCarrito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetalleCarrito>
 *
 * @method DetalleCarrito|null find($id, $lockMode = null, $lockVersion = null)
 * @method DetalleCarrito|null findOneBy(array $criteria, array $orderBy = null)
 * @method DetalleCarrito[]    findAll()
 * @method DetalleCarrito[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DetalleCarritoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetalleCarrito::class);
    }

//    /**
//     * @return DetalleCarrito[] Returns an array of DetalleCarrito objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DetalleCarrito
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    

public function findByCarrito($carrito, $iva) {
    $query = $this->getEntityManager()->createQueryBuilder();
    $query->select(
        't.id AS tienda_id',
        'MAX(cd.ciudad) AS ciudad',
        'MAX(pv.provincia) AS provincia',
        'MAX(pv.Region) AS region',
        'SUM(c.cantidad) AS total_cantidad',
        'SUM(p.peso * c.cantidad) AS total_peso',
        'SUM(CASE 
            WHEN v.id IS  NULL THEN 
                (CASE 
                    WHEN p.precio_rebajado_producto IS NOT NULL THEN 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                p.precio_rebajado_producto + ((p.precio_rebajado_producto * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                p.precio_rebajado_producto / (1 + (:iva / 100))
                            ELSE 
                                p.precio_rebajado_producto
                         END)
                    ELSE 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                p.precio_normal_producto + ((p.precio_normal_producto * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                p.precio_normal_producto / (1 + (:iva / 100))
                            ELSE 
                                p.precio_normal_producto
                         END)
                 END)
            ELSE 
                (CASE 
                    WHEN v.precio_rebajado IS NOT NULL THEN 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                v.precio_rebajado + ((v.precio_rebajado * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                v.precio_rebajado / (1 + (:iva / 100))
                            ELSE 
                                v.precio_rebajado
                         END)
                    ELSE 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                v.precio + ((v.precio * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                v.precio / (1 + (:iva / 100))
                            ELSE 
                                v.precio
                         END)
                 END)
            END * c.cantidad) AS total_precio'
    )
    ->from('App:detallecarrito', 'c')
    ->innerJoin('c.IdProducto', 'p')
    ->leftJoin('c.IdVariacion', 'v')
    ->innerJoin('p.tienda', 't')
    ->innerJoin('c.carrito', 'a')
    ->innerJoin('p.direcciones','u')
    ->innerJoin('u.ciudad','cd')
    ->innerJoin('cd.provincia', 'pv')
    ->where('a.id = :id')
    ->andWhere('p.entrgas_tipo = 1')
    ->andWhere('p.productos_tipo != 4')
    ->andWhere('p.disponibilidad_producto = :disponibilidad')
    ->groupBy('t.id')
    ->setParameter('id', $carrito)
    ->setParameter('iva', $iva)
    ->setParameter('disponibilidad',true)
    ;
    
    return $query->getQuery()->getResult();
}


public function carrito_delivereo($carrito) {
    $query = $this->getEntityManager()->createQueryBuilder();
    $query->select('c')
        ->from('App:detallecarrito', 'c')
        ->innerJoin('c.IdProducto', 'p')
        ->innerJoin('p.tienda', 't')
        ->innerJoin('c.carrito', 'a')
        ->where('a.id = :id')
        ->andWhere('p.entrgas_tipo = 1')
        ->andWhere('p.productos_tipo != 4')
        ->andWhere('p.disponibilidad_producto = :disponibilidad')
        ->groupBy('t.id')
        ->setParameter('id',$carrito)
        ->setParameter('disponibilidad',true)
        ;
        
    return $query->getQuery()->getResult();
}

public function  carrito_delivereo_tienda($carrito, $tienda){

    $query = $this->getEntityManager()->createQueryBuilder();
    $query->select('c')
        ->from('App:detallecarrito', 'c')
        ->innerJoin('c.IdProducto', 'p')
        ->innerJoin('p.tienda', 't')
        ->innerJoin('c.carrito', 'a')
        ->where('a.id = :id')
        ->andWhere('t.id = :tienda')
        ->andWhere('p.entrgas_tipo = 1')
        ->andWhere('p.productos_tipo != 4')
        ->andWhere('p.disponibilidad_producto = :disponibilidad')
        ->setParameter('tienda', $tienda)
        ->setParameter('id',$carrito)
        ->setParameter('disponibilidad',true)
        ;
        
    return $query->getQuery()->getResult();
}


public function findByCarrito_tienda($carrito,$iva,$tienda) {
    $query = $this->getEntityManager()->createQueryBuilder();
    $query->select(
            't.id AS tienda_id',
            'MAX(cd.ciudad) AS ciudad',
            'MAX(pv.provincia) AS provincia',
            'MAX(pv.Region) AS region',
            'SUM(c.cantidad) AS total_cantidad',
            'SUM(p.peso * c.cantidad) AS total_peso',
             'SUM(CASE 
              WHEN v.id IS NULL THEN 
                (CASE 
                    WHEN p.precio_rebajado_producto IS NOT NULL THEN 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                p.precio_rebajado_producto + ((p.precio_rebajado_producto * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                p.precio_rebajado_producto / (1 + (:iva / 100))
                            ELSE 
                                p.precio_rebajado_producto
                         END)
                    ELSE 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                p.precio_normal_producto + ((p.precio_normal_producto * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                p.precio_normal_producto / (1 + (:iva / 100))
                            ELSE 
                                p.precio_normal_producto
                         END)
                 END)
            ELSE 
                (CASE 
                    WHEN v.precio_rebajado IS NOT NULL THEN 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                v.precio_rebajado + ((v.precio_rebajado * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                v.precio_rebajado / (1 + (:iva / 100))
                            ELSE 
                                v.precio_rebajado
                         END)
                    ELSE 
                        (CASE 
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = false THEN 
                                v.precio + ((v.precio * :iva) / 100)
                            WHEN p.tiene_iva = true AND p.impuestos_incluidos = true THEN 
                                v.precio / (1 + (:iva / 100))
                            ELSE 
                                v.precio
                         END)
                 END)
            END * c.cantidad) AS total_precio'
        )
        ->from('App:detallecarrito', 'c')
        ->innerJoin('c.IdProducto', 'p')
        ->leftJoin('c.IdVariacion', 'v')
        ->innerJoin('p.tienda', 't')
        ->innerJoin('c.carrito', 'a')
        ->innerJoin('p.direcciones','u')
        ->innerJoin('u.ciudad','cd')
        ->innerJoin('cd.provincia', 'pv')
        ->where('a.id = :id')
        ->andWhere('p.entrgas_tipo = 1')
        ->andWhere('t.id = :tienda')
        ->andWhere('p.productos_tipo != 4')
        ->andWhere('p.disponibilidad_producto = :disponibilidad')
        ->setParameter('id',$carrito)
        ->setParameter('tienda', $tienda)
        ->setParameter('iva',$iva)
        ->setParameter('disponibilidad',true)
        ;
        
    return $query->getQuery()->getResult();
}

}
