<?php

namespace App\Entity\Traits;

use App\Exceptions\ExceptionService;
use App\Exceptions\FieldValidateException;
use Doctrine\ORM\Mapping as ORM;

trait NameTrait
{

    /**
     * @var string
     *
     * @ORM\Column(name="NAME", type="string", length=250, nullable=false)
     */
    private string $name = '';

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
     * @throws FieldValidateException|\Exception
     */
    public function setName(string $strNewName): self
    {
        if (!($strNewName = trim($strNewName))) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_EMPTY, FieldValidateException::class);
        }

        if (strlen($strNewName) < 3) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_SHORT, FieldValidateException::class);
        }

        $this->name = $strNewName;

        return $this;
    }
}
