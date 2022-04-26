<?php

namespace App\Entity;

use App\Entity\General\FieldValidator;
use Doctrine\ORM\Mapping as ORM;

/**
 * TRecipeTag
 *
 * @ORM\Table(name="t_recipe_tag")
 * @ORM\Entity(repositoryClass="App\Repository\RecipeTagRepository")
 */
class TRecipeTag extends BaseEntity
{
    /**
     * @var TRecipe
     *
     * @ORM\ManyToOne(targetEntity="TRecipe", inversedBy="tags")
     * @ORM\JoinColumn(name="RECIPE", referencedColumnName="ID", onDelete="CASCADE")
     */
    private TRecipe $recipe;

    /**
     * @var TTag
     * @ORM\ManyToOne(targetEntity="TTag")
     * @ORM\JoinColumn(name="TAG", referencedColumnName="ID", onDelete="CASCADE")
     */
    private TTag $tag;


    /**
     * @var int
     *
     * @ORM\Column(name="SORT", type="integer", nullable=false)
     */
    private int $sort;

    /**
     * @return int
     */
    public function getRecipe(): int
    {
        return $this->recipe->getId();
    }

    /**
     * @param TRecipe $recipe
     * @return $this
     */
    public function setRecipe(TRecipe $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    /**
     * @return TTag
     */
    public function getTag(): TTag
    {
        return $this->tag;
    }

    /**
     * @param TTag $tag
     * @return $this
     */
    public function setTag(TTag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return $this
     * @throws \Exception
     */
    public function setSort(int $sort): self
    {
        FieldValidator::v($sort)->validateRange(1);
        $this->sort = $sort;

        return $this;
    }
}
