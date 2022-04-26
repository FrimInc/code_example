<?php

namespace App\Entity;

use App\Entity\General\FieldValidator;
use App\Entity\Traits\NameTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TIngredientType
 *
 * @ORM\Table(name="t_ingredient_type")
 * @ORM\Entity(repositoryClass="App\Repository\IngredientTypeRepository")
 */
class TIngredientType extends BaseEntity
{
    use NameTrait;

    /**
     * @var int $sort
     *
     * @ORM\Column(name="SORT", type="integer", nullable=false)
     */
    private int $sort = 0;

    /**
     * @return int|null
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * @param int $intSort
     * @return self
     * @throws \App\Exceptions\FieldValidateException
     */
    public function setSort(int $intSort = 0): self
    {
        FieldValidator::v($intSort)->validateRange(1);
        $this->sort = $intSort;
        return $this;
    }
}
