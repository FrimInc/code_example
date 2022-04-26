<?php

namespace App\Entity;

use App\Entity\General\Accessible;
use App\Entity\Traits\NameTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TMenu
 *
 * @ORM\Table(name="t_menu",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="t_menu_NAME_uindex", columns={"NAME"})})
 * @ORM\Entity(repositoryClass="App\Repository\MenuRepository")
 */
class TMenu extends Accessible
{
    use NameTrait;

    public const VIEW_LINK   = '/menu/#id#';
    public const EDIT_LINK   = '/menu/#id#/edit';
    public const DELETE_LINK = '/app/menus/delete';

    /**
     * @var string $week
     *
     * @ORM\Column(name="WEEK", type="string", nullable=false)
     */
    private string $week = '{}';

    /**
     * @var bool $current
     *
     * @ORM\Column(name="current", type="boolean", nullable=false)
     */
    private bool $current = false;

    /**
     * @return bool
     */
    public function getIsCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @param bool $current
     * @return self
     */
    public function setIsCurrent(bool $current): self
    {
        $this->current = $current;
        return $this;
    }

    /**
     * @param array $arNewWeek
     * @return $this
     */
    public function setWeek(array $arNewWeek): self
    {
        foreach ($arNewWeek['days'] as &$arDay) {
            foreach ($arDay['meals'] as &$arMeal) {
                $arMeal['recipes']     = array_filter($arMeal['recipes']);
                $arMeal['ingredients'] = array_filter($arMeal['ingredients']);
            }
        }

        $this->week = @json_encode($arNewWeek);

        return $this;
    }

    /**
     * @param array $arDish
     * @param array $arMenu
     * @return array
     */
    private static function compatDish(array $arDish, array $arMenu): array
    {

        $arDish['amount'] ??= $arMenu['amount'] ?? 2;

        return $arDish;
    }

    /**
     * @param array $arWeek
     * @return array
     */
    private static function compatWeek(array $arWeek): array
    {

        $arDefMenu = self::getDefaultWeek();

        foreach ($arDefMenu as $strKey => $mixedDefaultValue) {
            $arWeek[$strKey] ??= $mixedDefaultValue;
        }

        return $arWeek;
    }

    /**
     * @return array
     */
    public function getWeek(): array
    {
        $arTmpWeek = @json_decode($this->week, true) ?: self::getDefaultWeek();

        $arTmpWeek = count($arTmpWeek['days']) ? $arTmpWeek : self::getDefaultWeek();

        $arTmpWeek = self::compatWeek($arTmpWeek);

        foreach ($arTmpWeek['days'] as &$arDay) {
            foreach ($arDay['meals'] as &$arMeal) {
                foreach ($arMeal as &$arMealType) {
                    foreach ($arMealType as &$arDish) {
                        $arDish = self::compatDish($arDish, $arTmpWeek);
                    }
                    $arMealType = array_filter($arMealType);
                }
            }
        }

        return $arTmpWeek;
    }

    /**
     * @return array
     */
    public function getIngredients(): array
    {
        $arIngredients    = [];
        $arIngredientsTmp = [];

        $arWeek = $this->getWeek();
        foreach ($arWeek['days'] as $arDay) { //@TODO пересчет родного рецепта по меню
            foreach ($arDay['meals'] as $arMeal) {
                foreach ($arMeal as $arMealType) {
                    foreach ($arMealType as $arDish) {
                        if (array_key_exists('ingredient', $arDish)) {
                            $arIngredientsTmp[] = $arDish;
                        } elseif (array_key_exists('recipe', $arDish)) {
                            foreach ($arDish['recipe']['ingredients'] as $arIngredient) {
                                $arIngredient['amount'] = ceil(
                                    ($arIngredient['amount'] / $arDish['recipe']['serving'])
                                    * $arDish['amount']
                                );
                                $arIngredientsTmp[]     = $arIngredient;
                            }
                        }
                    }
                }
            }
        }

        foreach ($arIngredientsTmp as $arIngredient) {
            if (!array_key_exists($strCurrentKey = $arIngredient['ingredient']['name'], $arIngredients)) {
                $arIngredients[$strCurrentKey] = $arIngredient;
            } else {
                $arIngredients[$strCurrentKey]['amount'] += $arIngredient['amount'];
            }
        }

        usort($arIngredients, function ($arOne, $arTwo) {
            return $arOne['amount'] < $arTwo['amount'];
        });

        return array_values($arIngredients);
    }

    /**
     * @return array
     */
    public function getMeals(): array
    {
        $arMeals = [];
        foreach ($this->getWeek()['days'] as $arDay) {
            $arMeals += array_keys($arDay['meals']);
        }
        $arMeals = array_unique($arMeals);
        return count($arMeals) ? $arMeals : self::getDefaultMeals();
    }

    /**
     * @return array
     */
    public static function getDefaultDay(): array
    {
        return [
            'meals' => array_fill_keys(self::getDefaultMeals(), self::getDefaultMeal())
        ];
    }

    /**
     * @return array
     */
    public static function getDefaultMeals(): array
    {
        return [
            'Завтрак',
            'Обед',
            'Ужин',
        ];
    }

    /**
     * @return array
     */
    public static function getDefaultMeal(): array
    {
        return [
            'recipes'     => [],
            'ingredients' => [],
        ];
    }

    /**
     * @return array
     */
    public static function getDefaultWeek(): array
    {
        return [
            'amount' => 2,
            'days'   => array_fill(0, 7, self::getDefaultDay())
        ];
    }
}
