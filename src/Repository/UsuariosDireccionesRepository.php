<?php

namespace App\Repository;

use App\Entity\UsuariosDirecciones;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UsuariosDirecciones>
 *
 * @method UsuariosDirecciones|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsuariosDirecciones|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsuariosDirecciones[]    findAll()
 * @method UsuariosDirecciones[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuariosDireccionesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsuariosDirecciones::class);
    }

//    /**
//     * @return UsuariosDirecciones[] Returns an array of UsuariosDirecciones objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UsuariosDirecciones
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
