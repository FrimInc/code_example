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
use App\Tools\StringTools;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadVkuso extends Command
{
    protected static $defaultName = 'app:load-vkuso';

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

    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        ini_set('memory_limit', '4096m');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        require_once $this->projectDir . '/dev/simple_html_dom.php';

        set_time_limit(0);

        if (!file_exists($strVkusoDir = $this->projectDir . '/var/parsers/vkuso/recipe')) {
            return Command::FAILURE;
        }

        $arDirs = scandir($strVkusoDir);
        unset($arDirs[0]);
        unset($arDirs[1]);

        foreach ($arDirs as $strSubDir) {
            try {
                $strCurrentFile = $strVkusoDir . '/' . $strSubDir . '/index.html';

                if (!file_exists($strCurrentFile)) {
                    continue;
                }

                $strHtml = file_get_contents($strCurrentFile);

                echo 'DO ' . $strCurrentFile . ' size ' . strlen($strHtml) . PHP_EOL;

                $arFields = [
                    'xmlid'  => $strSubDir,
                    'access' => 'O'
                ];

                if (static::$obRecipeRepository->findOneBy(['xmlid' => $arFields['xmlid']])) {
                    continue;
                }

                if (!($html = file_get_html($strCurrentFile))) {
                    continue;
                }


                /*
                'id'          => $obRequest->get('id') ?: 0,
                'name'        => $obRequest->get('name'),
                'ingredients' => $obRequest->get('ingredients'),
                'tags' => $obRequest->get('ingredients'),
                'anounce'     => $obRequest->get('anounce'),
                'type'        => $obRequest->get('type'),
                'days'        => $obRequest->get('days'),
                'kkal'        => $obRequest->get('kkal'),
                'totalTime'   => $obRequest->get('totalTime'),
                'activeTime'  => $obRequest->get('activeTime'),
                'serving'     => $obRequest->get('serving'),
                'difficult'   => $obRequest->get('difficult'),

                'stages'      => $obRequest->get('stages')
                 */
                if ($find = $html->find('h1[itemprop="name"]', 0)) {
                    $arFields['name'] = $find->plaintext;
                } else {
                    continue;
                }

                $arFields['anounce'] = $arFields['name'];
                if ($find = $html->find('.recipe_desc p', 0)) {
                    $arFields['anounce'] = $find->plaintext;
                }
                $arFields['days']       = 2;
                $arFields['kkal']       = 0;
                $arFields['activeTime'] = 0;

                if ($find = $html->find('.recipe_info__cook_time .duration', 0)) {
                    $strTime = $find->plaintext;

                    switch (true) {
                        case preg_match('/PT([0-9]+)H([0-9]+)M/i', $strTime, $arMatches):
                            $arFields['activeTime'] = $arMatches[1] * 60 + $arMatches[2];
                            break;
                        case preg_match('/PT([0-9]+)H/i', $strTime, $arMatches):
                            $arFields['activeTime'] = $arMatches[1] * 60;
                            break;
                        case preg_match('/PT([0-9]+)M/i', $strTime, $arMatches):
                            $arFields['activeTime'] = $arMatches[1];
                            break;
                    }
                }

                $arFields['totalTime'] = $arFields['activeTime'];

                if ($find = $html->find('.recipe_info__prep_time .duration', 0)) {
                    $strTime = $find->plaintext;
                    switch (true) {
                        case preg_match('/PT([0-9]+)H([0-9]+)M/i', $strTime, $arMatches):
                            $arFields['totalTime'] += $arMatches[1] * 60 + $arMatches[2];
                            break;
                        case preg_match('/PT([0-9]+)H/i', $strTime, $arMatches):
                            $arFields['totalTime'] += $arMatches[1] * 60;
                            break;
                        case preg_match('/PT([0-9]+)M/i', $strTime, $arMatches):
                            $arFields['totalTime'] += $arMatches[1];
                            break;
                    }
                }

                $arFields['serving'] = 2;
                if ($find = $html->find('.recipe_info__servings .recipe_info__value span', 0)) {
                    $arFields['serving'] = $find->plaintext * 1 ?: 2;
                }
                $arFields['difficult'] = 3;
                if ($find = $html->find('.recipe-difficulty span', 0)) {
                    $arFields['difficult'] = ([
                        'просто'  => 1,
                        'средняя' => 3,
                        'сложно'  => 5
                    ])[$find->plaintext ?: 'средняя'];
                }

                $arFields['stages'] = [];
                foreach ($html->find('.instructions.ver_2 li .instruction_description') as $stage) {
                    $arFields['stages'][] = trim($stage->plaintext);
                }

                if (count($arFields['stages']) == 0) {
                    foreach ($html->find('.instructions.ver_1 li') as $stage) {
                        $arFields['stages'][] = trim($stage->innertext);
                    }
                }

                if (count($arFields['stages']) == 0) {
                    foreach ($html->find('.instructions li') as $stage) {
                        $arFields['stages'][] = trim($stage->innertext);
                    }
                }

                $arFields['kkal'] = 0;

                $arTags = [];

                $strKitchen = $html->find('.recipe-cuisine span', 0)->plaintext;
                if ($strKitchen) {
                    $arTags[] = $strKitchen;
                }


                $arFields['tags'] = [];
                foreach ($arTags as $strTag) {
                    $obTag = static::$obTagRepository->findOneByName($strTag, self::$obAdminUser);

                    if (!$obTag) {
                        $obTag = static::$obTagRepository->put(
                            [
                                'name'   => StringTools::mb_ucfirst($strTag),
                                'access' => 'O'
                            ],
                            static::$obAdminUser
                        );
                    }

                    $arFields['tags'][] = [
                        'tag' => [
                            'name' => $obTag->getName(),
                            'id'   => $obTag->getId()
                        ]
                    ];
                }
                $rt = $html->find('.breadcrumbs span[itemprop="itemListElement"] span[itemprop="name"]');

                $arFields['type'] = array_pop($rt)->plaintext;

                $obType = static::$obTypeRepository->findOneByName($arFields['type']);
                if (!$obType) {
                    $obType = static::$obTypeRepository->put([
                        'name' => StringTools::mb_ucfirst($arFields['type'])
                    ]);
                }

                $arFields['type'] = $obType->getId();

                $arFields['ingredients'] = [];
                foreach ($html->find('.ingredient') as $ingredient) {
                    if ($ingredientName = $ingredient->find('.name', 0)->plaintext) {
                        if (!$ingredient->find('.list_value .value', 0)) {
                            $ingredientValue   = 'taste';
                            $ingredientMeasure = 'г';
                        } else {
                            $ingredientValue   = $ingredient->find('.list_value .value', 0)->plaintext;
                            $ingredientMeasure = $ingredient->find('.list_value .type', 0)->plaintext;

                            if (strstr($ingredientValue, '-')) {
                                $ingredientValue = explode('-', $ingredientValue);
                                if (count($ingredientValue = array_filter($ingredientValue))) {
                                    $ingredientValue = round(array_sum($ingredientValue) / count($ingredientValue));
                                }
                            } elseif ($ingredientValue === '½') {
                                $ingredientValue = 0.5;
                            }

                            $ingredientValue = floatval($ingredientValue);
                        }

                        $obIngredient = static::$obIngredientRepository->checkByName($ingredientName);

                        if (!$obIngredient) {
                            $ingredientMeasure = $ingredientMeasure ?: 'гр';
                            if ($ingredientMeasure === 'г') {
                                $ingredientMeasure = 'гр';
                            }
                            if (substr($ingredientMeasure, -1) !== '.') {
                                $ingredientMeasure .= '.';
                            }

                            $obMeasure = static::$obUnitRepository->findOneByShortName($ingredientMeasure);
                            if (!$obMeasure) {
                                $obMeasure = static::$obUnitRepository->put([
                                    'name' => $ingredientMeasure,
                                    'step' => 1
                                ]);
                                echo 'ADD ME ' . $ingredientMeasure . PHP_EOL;
                            }

                            $obIngredient = static::$obIngredientRepository->put(
                                [
                                    'name'    => StringTools::mb_ucfirst($ingredientName),
                                    'units'   => $obMeasure->getId(),
                                    'type'    => static::$obSomeType,
                                    'access'  => 'O',
                                    'minimum' => $obMeasure->getStep()
                                ],
                                static::$obAdminUser
                            );
                        }

                        $arFields['ingredients'][] = [
                            'ingredient' => [
                                'name' => $obIngredient->getName(),
                                'id'   => $obIngredient->getId()
                            ],
                            'amount'     => $ingredientValue == 'taste' ? 1 : $ingredientValue,
                            'taste'      => $ingredientValue == 'taste'
                        ];

                    }
                }

                $arRes = self::$obRecipeRepository->put($arFields, self::$obAdminUser);
            } catch (Exception $eException) {
                echo '<pre>' . print_r(array(__FILE__, __LINE__, date('d.m.Y H:i:s'), $eException->getMessage(), $eException->getTraceAsString()), true) . '</pre>';
                return Command::FAILURE;
            }

        }

        return Command::SUCCESS;
    }
}