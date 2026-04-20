<?php

namespace App\Repository;

use App\Entity\Retiros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Retiros>
 *
 * @method Retiros|null find($id, $lockMode = null, $lockVersion = null)
 * @method Retiros|null findOneBy(array $criteria, array $orderBy = null)
 * @method Retiros[]    findAll()
 * @method Retiros[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RetirosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Retiros::class);
    }

//    /**
//     * @return Retiros[] Returns an array of Retiros objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Retiros
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


public function retiros_filter($ganancia,$estado = null,)
{

    $query = $this->getEntityManager()->createQueryBuilder();

    $query
    ->select('r')
    ->from('App:retiros','r')
    ->innerJoin('r.ganancia','g')
    ->where('g.id = :id_ganancia')
    ->setParameter('id_ganancia',$ganancia);

   
    if ($estado !== null) {
        $query->andWhere('r.estado = :query_estado')
            ->setParameter('query_estado',$estado);
    }

    return $query->getQuery()->getResult();

}


}
