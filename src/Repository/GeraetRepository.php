<?php

namespace App\Repository;

use App\Entity\Geraet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Geraet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Geraet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Geraet[]    findAll()
 * @method Geraet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeraetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Geraet::class);
    }

    // /**
    //  * @return Geraet[] Returns an array of Geraet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Geraet
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
