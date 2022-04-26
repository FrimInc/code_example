<?php

namespace App\Entity;

use App\Entity\Traits\NameTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TType
 *
 * @ORM\Table(name="t_type", indexes={@ORM\Index(name="idx_typename", columns={"NAME"})})
 * @ORM\Entity(repositoryClass="App\Repository\TypeRepository")
 */
class TType extends BaseEntity
{
    use NameTrait;

    /**
     * @var TType|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TType")
     * @ORM\JoinColumn(name="PARENT", referencedColumnName="ID")
     */
    private ?TType $parent = null;

    /**
     * @var TType[]
     */
    public array $childs = [];

    /**
     * @return TType:null
     */
    public function getParent(): ?TType
    {
        return ($this->parent && $this->parent->getId() != $this->getId()) ? $this->parent : null;
    }

    /**
     * @param TType $parent
     * @return $this
     */
    public function setParent(TType $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
}
