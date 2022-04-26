<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TRecipeTools
 *
 * @ORM\Table(name="t_recipe_tools", indexes={@ORM\Index(name="idx_recipe", columns={"RECIPE"})})
 * @ORM\Entity
 */
class TRecipeTools extends BaseEntity
{

    /**
     * @var int
     *
     * @ORM\Column(name="RECIPE", type="integer", nullable=false)
     */
    private int $recipe;

    /**
     * @var int
     *
     * @ORM\Column(name="TOOL", type="integer", nullable=false)
     */
    private int $tool;

    /**
     * @return int|null
     */
    public function getRecipe(): ?int
    {
        return $this->recipe;
    }

    /**
     * @param int $recipe
     * @return $this
     */
    public function setRecipe(int $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTool(): ?int
    {
        return $this->tool;
    }

    /**
     * @param int $tool
     * @return $this
     */
    public function setTool(int $tool): self
    {
        $this->tool = $tool;

        return $this;
    }
}
