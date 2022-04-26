<?php

namespace App\Entity;

use App\Entity\General\FieldValidator;
use Doctrine\ORM\Mapping as ORM;

/**
 * TRecipeIngredient
 *
 * @ORM\Table(name="t_recipe_ingredient", indexes={
 * @ORM\Index(name="idx_recipe", columns={"RECIPE"}),
 * @ORM\Index(name="idx_amount", columns={"AMOUNT"})})
 * @ORM\Entity(repositoryClass="App\Repository\RecipeIngredientRepository")
 */
class TRecipeIngredient extends BaseEntity
{
    /**
     * @var float
     *
     * @ORM\Column(name="AMOUNT", type="float", nullable=false)
     */
    private float $amount;

    /**
     * @var bool
     *
     * @ORM\Column(name="taste", type="boolean", nullable=false)
     */
    private bool $taste;

    /**
     * @var TRecipe
     *
     * @ORM\ManyToOne(targetEntity="TRecipe", inversedBy="ingredients")
     * @ORM\JoinColumn(name="RECIPE", referencedColumnName="ID", onDelete="CASCADE")
     */
    private TRecipe $recipe;

    /**
     * @var TIngredient
     * @ORM\ManyToOne(targetEntity="TIngredient")
     * @ORM\JoinColumn(name="INGREDIENT", referencedColumnName="ID", onDelete="CASCADE")
     */
    private TIngredient $ingredient;

    /**
     * @var int
     *
     * @ORM\Column(name="SORT", type="integer", nullable=false)
     */
    private int $sort;

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function setAmount(float $amount): self
    {
        FieldValidator::v($amount)->validateRange(0.01);
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getTaste(): ?bool
    {
        return $this->taste;
    }

    /**
     * @param bool $taste
     * @return $this
     * @throws \Exception
     */
    public function setTaste(bool $taste): self
    {
        $this->taste = $taste;

        return $this;
    }

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
     * @return TIngredient
     */
    public function getIngredient(): TIngredient
    {
        return $this->ingredient;
    }

    /**
     * @param TIngredient $ingredient
     * @return $this
     */
    public function setIngredient(TIngredient $ingredient): self
    {
        $this->ingredient = $ingredient;

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
