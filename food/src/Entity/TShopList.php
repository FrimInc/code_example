<?php

namespace App\Entity;

use App\Entity\General\Accessible;
use App\Entity\Traits\NameTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TShopList
 *
 * @ORM\Table(name="t_shop_list")
 * @ORM\Entity(repositoryClass="App\Repository\ShopListRepository")
 */
class TShopList extends Accessible
{
    use NameTrait;

    public const VIEW_LINK   = '/shopList/#id#';
    public const EDIT_LINK   = '/shopList/#id#';
    public const DELETE_LINK = '/app/shopLists/delete';

    /**
     * @var string
     *
     * @ORM\Column(name="LIST", type="string", nullable=false)
     */
    private string $list = '{}';

    /**
     * @var bool $main
     *
     * @ORM\Column(name="MAIN", type="boolean", nullable=false)
     */
    private bool $main = false;

    /**
     * @return array|null
     */
    public function getList(): array
    {

        if ($arList = @json_decode($this->list, true)) {
            usort($arList['ingredients'], function ($a, $b) {
                return $a['ingredient']['name'] > $b['ingredient']['name'];
            });
            foreach ($arList['ingredients'] as &$arListItem) {
                $arListItem['isChecked'] = $arListItem['isChecked'] ?? false;
            }
            return $arList;
        }

        return [
            'ingredients' => []
        ];
    }

    /**
     * @param bool $boolFilterChecked
     * @return array|null
     */
    public function getGroupedList($boolFilterChecked = null): array
    {
        if (count($arIngredients = $this->getList()['ingredients'])) {
            $arGrouped     = [];
            $arIngredients = array_filter($arIngredients);

            if ($boolFilterChecked !== null) {
                $arIngredients = array_filter(
                    $arIngredients,
                    function ($arIngredientItem) use ($boolFilterChecked) {
                        return $arIngredientItem['isChecked'] == $boolFilterChecked;
                    }
                );
            }

            foreach ($arIngredients as $intIngredientIndex => $arIngredient) {
                $intTypeId = $arIngredient['ingredient']['type']['id'];
                if (!array_key_exists($intTypeId, $arGrouped)) {
                    $arGrouped[$intTypeId]['group']         = $arIngredient['ingredient']['type'];
                    $arGrouped[$intTypeId]['group']['sort'] ??= 1;
                }
                $arGrouped[$intTypeId]['ingredients'][$intIngredientIndex] = $arIngredient;
            }

            usort($arGrouped, function ($a, $b) {
                return $a['group']['sort'] > $b['group']['sort'];
            });

            return array_values($arGrouped);
        }

        return [];
    }


    /**
     * @param array $arNewList
     * @param array $arAdditionalList
     * @return array
     */
    public function normalizeListAmount(array $arNewList, array $arAdditionalList = []): array
    {

        $arListToSet = [];
        foreach ($arNewList['ingredients'] as $arListItem) {
            if (!array_key_exists($arListItem['ingredient']['id'], $arListToSet)) {
                $arListToSet[$arListItem['ingredient']['id']] = $arListItem;
            } else {
                $arListToSet[$arListItem['ingredient']['id']]['amount'] += $arListItem['amount'];
            }
        }
        if (array_key_exists('ingredients', $arAdditionalList)) {
            foreach ($arAdditionalList['ingredients'] as $arListItem) {
                if ($arListItem['isChecked']) {
                    continue;
                }
                if (!array_key_exists($arListItem['ingredient']['id'], $arListToSet)) {
                    $arListToSet[$arListItem['ingredient']['id']] = $arListItem;
                } else {
                    $arListToSet[$arListItem['ingredient']['id']]['amount'] += $arListItem['amount'];
                }
            }
        }
        $arNewList['ingredients'] = $arListToSet;
        return $arNewList;
    }

    /**
     * @param array $arNewList
     * @param array $arAdditionalList
     * @return $this
     */
    public function setList(array $arNewList, array $arAdditionalList = []): self
    {
        $this->list = @json_encode($this->normalizeListAmount($arNewList, $arAdditionalList));

        return $this;
    }

    /**
     * @param bool $boolNewMain
     * @return $this
     */
    public function setMain(bool $boolNewMain): self
    {

        $this->main = $boolNewMain;

        return $this;
    }

    /**
     * @return bool
     */
    public function getMain(): bool
    {
        return $this->main;
    }
}
