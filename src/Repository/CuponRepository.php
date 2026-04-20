<?php

namespace App\Repository;

use App\Entity\Cupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cupon>
 *
 * @method Cupon|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cupon|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cupon[]    findAll()
 * @method Cupon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CuponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cupon::class);
    }


public function cupon_usuario($searchTerm, $user, $tienda) {
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('c')
        ->from('App:Cupon', 'c')
        ->innerJoin('c.login', 'l')
        ->where('c.codigo_cupon = :searchTerm')
        ->andWhere('(c.tienda IS NULL OR c.tienda <> :tienda)')
        ->andWhere('l.id = :user')
        ->andWhere('c.activo = :value')
        ->setParameter('value', true)
        ->setParameter('user', $user)
        ->setParameter('searchTerm', $searchTerm)
        ->setParameter('tienda', $tienda);

    return $query->getQuery()->getResult();
}
 


public function cupon_productos ($searchTerm,$tienda){
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('c')
        ->from('App:cupon', 'c')
        ->innerJoin('c.productos','l')
        ->Where( 'c.codigo_cupon = :searchTerm' )
        ->andWhere('(c.tienda IS NULL OR c.tienda <> :tienda)')
        ->andWhere('l.productos_tipo <> :value')
        ->setParameter('value',3)
        ->setParameter('searchTerm', $searchTerm)
        ->setParameter('tienda', $tienda)
        ;

        return $query->getQuery()->getResult();
}

public function cupon_producto($searchTerm, $producto,$tienda){

    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('c')
        ->from('App:cupon', 'c')
        ->join('c.productos','l')
        ->Where( 'c.codigo_cupon = :searchTerm' )
        ->andWhere('l.id = :producto')
        ->andWhere('c.activo = :estado')
        ->andWhere('l.productos_tipo <> :value')
        ->andWhere('(c.tienda IS NULL OR c.tienda <> :tienda)')
        ->setParameter('value',value: 3)
        ->setParameter('estado', true)
        ->setParameter('producto', $producto)
        ->setParameter('searchTerm', $searchTerm)
        ->setParameter('tienda', $tienda)
        ->getMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
}


function uso_cupon($searchTerm,$user){
           
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('p')
    ->from('App:pedidos', 'p')
    ->innerJoin('p.cupon','c')
    ->where('p.estado = :value')
    ->andWhere('p.login = :login')
    ->andWhere('c.codigo_cupon = :searchTerm')
    ->setParameter('login', $user)
    ->setParameter('value','APPROVED')
    ->setParameter('searchTerm', $searchTerm);

    return $query->getQuery()->getResult();  

}


public function cupones_referido($tienda,$tipo=null){
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('c')
    ->from('App:cupon', 'c')
    ->where('c.tienda = :tienda')
    ->setParameter('tienda', $tienda);

    if($tipo){
        $query->andWhere('c.tipo =: tipo')
        ->setParameter('tipo', $tipo);
         
    }

    return $query->getQuery()->getResult();
}

 public function cupon_referido($tienda,$id){
    $query = $this->getEntityManager()->createQueryBuilder();

    $query->select('c')
    ->from('App:cupon', 'c')
    ->Where('c.id = :id')
    ->andwhere('c.tienda = :tienda')
    ->setParameter('tienda',$tienda)
    ->setParameter('id', $id)
    ;

    return $query->getQuery()->getOneOrNullResult();
 }

}
