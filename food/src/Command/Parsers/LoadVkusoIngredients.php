<?php

namespace App\Command\Parsers;

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
use Symfony\Component\HttpKernel\KernelInterface;

class LoadVkusoIngredients extends Command
{
    protected static                          $defaultName     = 'app:load-vkuso-ingredients';
    protected static RecipeRepository         $obRecipeRepository;
    protected static IngredientRepository     $obIngredientRepository;
    protected static IngredientTypeRepository $obIngredientTypeRepository;
    protected static TypeRepository           $obTypeRepository;
    protected static UnitRepository           $obUnitRepository;
    protected static TagRepository            $obTagRepository;
    protected static ?TUsers                  $obAdminUser;
    protected static ?TIngredientType         $obSomeType;
    protected static ?EntityManagerInterface  $obEntityManager = null;

    private string $projectDir;

    public function __construct(
        KernelInterface $kernel,
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
        $this->projectDir                 = $kernel->getProjectDir();
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

        require_once $this->projectDir . '/dev/simple_html_dom.php';

        set_time_limit(0);

        if (!file_exists($strVkusoDir = $this->projectDir . '/var/parsers/vkuso/ingredients')) {
            return Command::FAILURE;
        }

        $arDirs = scandir($strVkusoDir);
        unset($arDirs[0]);
        unset($arDirs[1]);

        $intTotal   = $arDirs;
        $intCurrent = 0;

        $arTypeCache = [];

        foreach ($arDirs as $strSubDir) {
            $intCurrent++;
            try {
                $strCurrentFile = $strVkusoDir . '/' . $strSubDir . '/index.html';

                if (!file_exists($strCurrentFile)) {
                    continue;
                }

                $strHtml = file_get_contents($strCurrentFile);

                echo 'DO ' . $strCurrentFile . ' size ' . strlen($strHtml) . PHP_EOL;

                if (!($html = file_get_html($strCurrentFile))) {
                    continue;
                }

                $strName = $html->find('.breadcrumbs .current span', 0)->plaintext;
                $strType = $html->find('.breadcrumbs span span', 1)->plaintext;

                if (!$strName || !$strType) {
                    continue;
                }

                if (!($obIngredient = static::$obIngredientRepository->findOneByName($strName, self::$obAdminUser))) {
                    continue;
                }

                if (array_key_exists($strType, $arTypeCache)) {
                    $obType = $arTypeCache[$strType];
                } else {

                    if (!($obType = static::$obIngredientTypeRepository->findOneByName($strType))) {
                        $obType = static::$obIngredientTypeRepository->put(['name' => $strType, 'sort' => 500]);
                    }
                    $arTypeCache[$strType] = $obType;
                }

                $obIngredient->setType($obType);
                static::$obEntityManager->persist($obIngredient);
                static::$obEntityManager->flush();

                //      echo 'DO ' . $strName . ' - ' . $strType . ' - ' . $intCurrent . ' of ' . $intTotal . PHP_EOL;

            } catch (Exception $eException) {
                echo '<pre>' . print_r(array(__FILE__, __LINE__, date('d.m.Y H:i:s'), $eException->getMessage(), $eException->getTraceAsString()), true) . '</pre>';
                return Command::FAILURE;
            }

        }

        return Command::SUCCESS;
    }
}