<?php

namespace App\Command\Resort;

use App\Constants;
use App\Entity\TIngredientType;
use App\Entity\TUsers;
use App\Repository\IngredientRepository;
use App\Repository\IngredientTypeRepository;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use App\Repository\TypeRepository;
use App\Repository\UnitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResortTypes extends Command
{
    protected static                          $defaultName     = 'app:resort-types';

    protected static RecipeRepository         $obRecipeRepository;
    protected static IngredientRepository     $obIngredientRepository;
    protected static IngredientTypeRepository $obIngredientTypeRepository;
    protected static TypeRepository           $obTypeRepository;
    protected static UnitRepository           $obUnitRepository;
    protected static TagRepository            $obTagRepository;
    protected static ?TUsers                  $obAdminUser;
    protected static ?TIngredientType         $obSomeType;
    protected static ?EntityManagerInterface  $obEntityManager = null;

    public function __construct(
        RecipeRepository $obRecipeRepository,
        IngredientRepository $obIngredientRepository,
        IngredientTypeRepository $obIngredientTypeRepository,
        TypeRepository $obTypeRepository,
        UnitRepository $obUnitRepository,
        UserRepository $obUserRepository,
        TagRepository $obTagRepository,
        EntityManagerInterface $obEntityManager
    )
    {
        parent::__construct();
        self::$obRecipeRepository         = $obRecipeRepository;
        self::$obIngredientRepository     = $obIngredientRepository;
        self::$obIngredientTypeRepository = $obIngredientTypeRepository;
        self::$obTypeRepository           = $obTypeRepository;
        self::$obUnitRepository           = $obUnitRepository;
        self::$obTagRepository            = $obTagRepository;
        self::$obAdminUser                = $obUserRepository->find(1);
        self::$obSomeType                 = $obTypeRepository->find(Constants::DEFAULT_TYPE);
        self::$obEntityManager            = $obEntityManager;
    }

    protected function configure()
    {
        parent::configure();
        ini_set('memory_limit', '4096m');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $arAllTypes = [];
        $arTypes    = [];
        try {

            foreach (self::$obIngredientTypeRepository->findAll() as $obType) {
                $arTypes[$obType->getId()]    = $obType;
                $arAllTypes[$obType->getId()] = 10000;
            }

            foreach (self::$obIngredientRepository->findAll() as $obIngredient) {
                $arAllTypes[$obIngredient->getType()->getId()]--;
            }

            foreach ($arTypes as $obType) {
                $obType->setSort($arAllTypes[$obType->getId()] ?: 10000);
                self::$obEntityManager->persist($obType);
            }
            self::$obEntityManager->flush();
        } catch (Exception $eException) {
            echo '<pre>' . print_r(array(__FILE__, __LINE__, date('d.m.Y H:i:s'), $eException->getFile() . $eException->getLine(), $eException->getMessage(), $eException->getTraceAsString()), true) . '</pre>';
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}