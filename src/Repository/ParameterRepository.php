<?php

namespace App\Repository;

use App\Entity\Parameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Parameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parameter[]    findAll()
 * @method Parameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parameter::class);
    }

    /**
     * @return Parameter[] Returns an array of Parameter objects
     *                     where selected = 1
     */
    public function findSelectedbyGeraetName(string $geraet_name)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.geraet', 'g')
            ->where('g.geraet_name = :geraetname')
            ->setParameter('geraetname', $geraet_name)
            ->AndWhere('p.parameter_selected = true')
            ->orderBy('p.sort_position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Parameter[] Returns an array of Parameter objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Parameter
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
