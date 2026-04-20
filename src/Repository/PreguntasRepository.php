<?php

namespace App\Repository;

use App\Entity\Preguntas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Preguntas>
 *
 * @method Preguntas|null find($id, $lockMode = null, $lockVersion = null)
 * @method Preguntas|null findOneBy(array $criteria, array $orderBy = null)
 * @method Preguntas[]    findAll()
 * @method Preguntas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreguntasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Preguntas::class);
    }

//    /**
//     * @return Preguntas[] Returns an array of Preguntas objects
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

//    public function findOneBySomeField($value): ?Preguntas
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


       public function preguntas_vendedor($tienda){

        $query = $this->getEntityManager()->createQueryBuilder();

        $query->select('p')
        ->from('App:preguntas', 'p')
        ->innerJoin('p.producto', 'pr') 
        ->where('pr.tienda = :val')
        ->setParameter('val', $tienda)
        ->orderBy('fecha', 'DESC')
        ;
    
       return $query->getQuery()->getResult();

       }
}
