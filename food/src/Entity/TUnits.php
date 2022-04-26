<?php

namespace App\Entity;

use App\Entity\General\FieldValidator;
use App\Entity\Traits\NameTrait;
use App\Exceptions\FieldValidateException;
use Doctrine\ORM\Mapping as ORM;

/**
 * TUnits
 *
 * @ORM\Table(name="t_units", uniqueConstraints={@ORM\UniqueConstraint(name="unuts_NAME_uindex", columns={"NAME"})})
 * @ORM\Entity(repositoryClass="App\Repository\UnitRepository")
 */
class TUnits extends BaseEntity
{
    use NameTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="SHORT", type="string", length=5, nullable=false)
     */
    private string $short;

    /**
     * @var float
     *
     * @ORM\Column(name="step", type="float")
     */
    private float $step;

    /**
     * @return string|null
     */
    public function getShort(): ?string
    {
        return $this->short;
    }

    /**
     * @param string $strNewShort
     * @return $this
     * @throws FieldValidateException
     */
    public function setShort(string $strNewShort): self
    {
        if (!($strNewShort = trim($strNewShort))) {
            throw new FieldValidateException('Название единицы измерения не может быть пустым');
        }

        $this->short = $strNewShort;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getStep(): ?float
    {
        return $this->step;
    }

    /**
     * @param float $step
     * @return self
     */
    public function setStep(float $step): self
    {
        FieldValidator::v($step)->validateRange(0.1, 1000);
        $this->step = $step;
        return $this;
    }
}
