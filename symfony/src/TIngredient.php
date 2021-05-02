<?php

namespace App\Entity;

use App\Controller\Ingredients;
use App\Exceptions\ExceptionFactory;
use App\Exceptions\FieldValidateException;
use App\Repository\Traits\AccessRestrictorTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * TIngredient
 *
 * @ORM\Table(name="t_ingredient", indexes={@ORM\Index(name="idx_name", columns={"NAME"}),
 * @ORM\Index(name="t_ingredient_t_unuts_ID_fk", columns={"UNITS"})})
 * @ORM\Entity(repositoryClass="App\Repository\IngredientRepository")
 */
class TIngredient extends BaseEntity
{
    use AccessRestrictorTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var string
     *
     * @ORM\Column(name="NAME", type="string", length=250, nullable=false)
     */
    private string $name;

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
     * @var string
     *
     * @ORM\Column(name="ACCESS", type="string", nullable=true)
     */
    private string $access;

    /**
     * @var TUsers $author
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TUsers")
     * @ORM\JoinColumn(name="AUTHOR", referencedColumnName="ID")
     */
    private TUsers $author;

    /**
     * @var string
     */
    private string $editLink = 'javascript:void(0);';

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $intId
     * @return null
     */
    public function setId(int $intId)
    {
        return $this->id = $intId;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $strNewName
     * @return $this
     * @throws FieldValidateException|\Exception|\Exception
     */
    public function setName(string $strNewName): self
    {
        if (!($strNewName = trim($strNewName))) {
            ExceptionFactory::getException(ExceptionFactory::INGREDIENT_NAME_EMPTY, FieldValidateException::class);
        }

        if (strlen($strNewName) < 3) {
            ExceptionFactory::getException(ExceptionFactory::INGREDIENT_NAME_SHORT, FieldValidateException::class);
        }

        $this->name = $strNewName;

        return $this;
    }

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
        if ($minimum < 1) {
            ExceptionFactory::getException(ExceptionFactory::INGREDIENT_MINIMUM, FieldValidateException::class);
        }

        $this->minimum = $minimum;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccess(): string
    {
        return $this->access;
    }

    /**
     * @param string $access
     * @return $this
     */
    public function setAccess(string $access): self
    {
        $this->access = $access;

        return $this;
    }

    /**
     * @return TUsers
     */
    public function getAuthor(): ?TUsers
    {
        return $this->author;
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return self
     */
    public function setAuthor(TUsers $obUser): self
    {
        $this->author = $obUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getEditLink(): string
    {
        return Ingredients::EDIT_LINK . '?id=' . $this->getId();
    }

    /**
     * @return string
     */
    public function getDeleteLink(): string
    {
        return Ingredients::DELETE_LINK . '?id=' . $this->getId();
    }
}
