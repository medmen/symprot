<?php

namespace App\Repository;

use App\Entity\Protocol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Protocol|null find($id, $lockMode = null, $lockVersion = null)
 * @method Protocol|null findOneBy(array $criteria, array $orderBy = null)
 * @method Protocol[]    findAll()
 * @method Protocol[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProtocolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Protocol::class);
    }

    public function findOneByIdWithGeraet(int $id): ?Protocol
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT p, g
            FROM App\Entity\Protocol p
            INNER JOIN p.geraet g
            WHERE p.id = :id'
        )->setParameter('id', $id);

        return $query->getOneOrNullResult();
    }

    // /**
    //  * @return Protocol[] Returns an array of Protocol objects
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
    public function findOneBySomeField($value): ?Protocol
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
