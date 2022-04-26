<?php

namespace App\Repository;

use App\Entity\TTag;
use App\Entity\TUsers;
use App\Repository\General\AccessProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TTag[]    findAll()
 * @method TTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends AccessProvider
{
    protected static ?EntityManagerInterface $obEntityManager = null;

    /**
     * Tag constructor.
     *
     * @param ManagerRegistry                      $registry
     * @param \Doctrine\ORM\EntityManagerInterface $obEntityManager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $obEntityManager)
    {
        parent::__construct($registry, TTag::class);
        static::$obEntityManager = $obEntityManager;
    }


    /**
     * @param array  $arFields
     * @param TUsers $obUser
     *
     * @return TTag id
     * @throws \App\Exceptions\FieldValidateException
     */
    public function put($arFields, TUsers $obUser): TTag
    {
        $obNewTag = new TTag();

        $obNewTag
            ->setName($arFields['name'])
            ->setAuthor($obUser)
            ->setAccess($arFields['access']);

        static::$obEntityManager->persist($obNewTag);
        static::$obEntityManager->flush();
        static::$obEntityManager->refresh($obNewTag);

        return $obNewTag;
    }
}
