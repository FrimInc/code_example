<?php

namespace App\Entity;

use App\Entity\General\Accessible;
use App\Entity\General\FieldValidator;
use App\Entity\Traits\NameTrait;
use App\Exceptions\ExceptionFactory;
use App\Exceptions\FieldValidateException;
use Doctrine\ORM\Mapping as ORM;

/**
 * TIngredient
 *
 * @ORM\Table(name="t_ingredient", indexes={@ORM\Index(name="idx_name", columns={"NAME"}),
 * @ORM\Index(name="t_ingredient_t_unuts_ID_fk", columns={"UNITS"})})
 * @ORM\Entity(repositoryClass="App\Repository\IngredientRepository")
 */
class TIngredient extends Accessible
{
    use NameTrait;

    /**
     * @var TUnits
     * @ORM\ManyToOne(targetEntity="App\Entity\TUnits")
     * @ORM\JoinColumn(name="UNITS", referencedColumnName="ID")
     */
    private TUnits $units;

    /**
     * @var TIngredientType
     * @ORM\ManyToOne(targetEntity="App\Entity\TIngredientType")
     * @ORM\JoinColumn(name="TYPE", referencedColumnName="ID")
     */
    private TIngredientType $type;

    /**
     * @var int
     *
     * @ORM\Column(name="MINIMUM", type="integer", nullable=false)
     */
    private int $minimum;

    /**
     * @return TUnits
     */
    public function getUnits(): TUnits
    {
        return $this->units;
    }

    /**
     * @param TUnits $obNewUnits
     * @return $this
     * @throws FieldValidateException|\Exception
     */
    public function setUnits(TUnits $obNewUnits): self
    {

        if (!$obNewUnits->getId()) {
            ExceptionFactory::getException(ExceptionFactory::INGREDIENT_UNITS, FieldValidateException::class);
        }

        $this->units = $obNewUnits;

        return $this;
    }

    /**
     * @return TIngredientType
     */
    public function getType(): TIngredientType
    {
        return $this->type;
    }

    /**
     * @param TIngredientType $obNewType
     * @return $this
     * @throws FieldValidateException|\Exception
     */
    public function setType(TIngredientType $obNewType): self
    {

        if (!$obNewType->getId()) {
            ExceptionFactory::getException(ExceptionFactory::INGREDIENT_TYPE, FieldValidateException::class);
        }

        $this->type = $obNewType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMinimum(): ?int
    {
        return $this->minimum;
    }

    /**
     * @param int $minimum
     * @return $this
     * @throws FieldValidateException|\Exception
     */
    public function setMinimum(int $minimum): self
    {
        FieldValidator::v($minimum)->validateRange(0.5);

        $this->minimum = $minimum;

        return $this;
    }
}
