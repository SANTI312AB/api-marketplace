<?php

namespace App\Repository;

use App\Entity\Servientrega;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Servientrega>
 *
 * @method Servientrega|null find($id, $lockMode = null, $lockVersion = null)
 * @method Servientrega|null findOneBy(array $criteria, array $orderBy = null)
 * @method Servientrega[]    findAll()
 * @method Servientrega[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServientregaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Servientrega::class);
    }

//    /**
//     * @return Servientrega[] Returns an array of Servientrega objects
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

//    public function findOneBySomeField($value): ?Servientrega
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

      public function excel_repository(){

    
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('s')
            ->from('App:servientrega', 's')
            ->innerJoin('s.pedido', 'pd')
            ->where('pd.estado_envio = 26')
            ->andWhere('s.excel_generado = :value')
            ->andWhere('s.metodo_envio = :value2')
            ->andWhere('s.anulado = :value3')
            ->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('pd.estado', ':val4')
                )
            )
            ->setParameter('val4', 'APPROVED')
            ->setParameter('value', false)
            ->setParameter('value2',1)
            ->setParameter('value3', false);
    
        return $query->getQuery()->getResult();

      }
      
}
