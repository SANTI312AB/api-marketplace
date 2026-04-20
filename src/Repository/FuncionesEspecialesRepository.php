<?php

namespace App\Repository;

use App\Entity\FuncionesEspeciales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FuncionesEspeciales>
 */
class FuncionesEspecialesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FuncionesEspeciales::class);
    }

    //    /**
    //     * @return FuncionesEspeciales[] Returns an array of FuncionesEspeciales objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FuncionesEspeciales
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function getValidationConfig(): array
    {
        $ids = [6, 7, 8];

        $rows = $this->createQueryBuilder('f')
            ->where('f.id IN (:ids)')
            ->andWhere('f.activo = true')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $config = [];
        foreach ($rows as $row) {
            // OJO: descripcion es string en DB, casteamos según ID
            switch ($row->getId()) {
                case 6: // número máximo de keys
                    $config['maxKeys'] = (int) $row->getDescripcion();
                    break;
                case 7: // profundidad
                    $config['maxDepth'] = (int) $row->getDescripcion();
                    break;
                case 8: // valores string
                    $config['onlyStrings'] = filter_var($row->getDescripcion(), FILTER_VALIDATE_BOOLEAN);
                    break;
            }
        }

        return $config;
    }
}
