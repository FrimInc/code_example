<?php

namespace App\Entity;

use App\Exceptions\ExceptionService;
use App\Exceptions\FieldValidateException;
use App\Repository\General\AccessProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use App\Entity\Annotations\SkipInJson;

/**
 * TUsers
 *
 * @ORM\Table(name="t_users", indexes={@ORM\Index(name="idx_ID", columns={"ID"}),
 * @ORM\Index(name="idx_LOGIN", columns={"LOGIN"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class TUsers extends BaseEntity implements UserInterface
{

    /**
     * @var string|null
     * @ORM\Column(name="NAME", type="string", length=255, nullable=true)
     */
    private ?string $name;

    /**
     * @var string
     * @ORM\Column(name="LOGIN", type="string", length=255, nullable=false)
     */
    private string $login;

    /**
     * @var string
     * @ORM\Column(name="PASSWORD", type="string", length=255, nullable=false)
     * @SkipInJson()
     */
    private string $password;

    /**
     * @var string|null
     * @ORM\Column(name="LAST_NAME", type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @var string
     * @ORM\Column(name="ROLES", type="string", length=255, nullable=false)
     * @SkipInJson()
     */
    private string $roles = 'ROLE_USER';

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $strNewName
     * @return $this
     * @throws \Exception
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

    /**
     * @param string $strNewLogin
     * @return $this
     * @throws \Exception
     */
    public function setLogin(string $strNewLogin): self
    {

        if (!($strNewLogin = trim($strNewLogin))) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_EMPTY, FieldValidateException::class);
        }

        if (strlen($strNewLogin) < 3) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_SHORT, FieldValidateException::class);
        }

        $obValidator = Validation::createValidator();

        $emailConstraint = new Assert\Email();

        $errors = $obValidator->validate(
            $strNewLogin,
            $emailConstraint
        );

        if (count($errors)) {
            ExceptionService::getException(ExceptionService::EMAIL_INVALID);
        }

        $this->login = $strNewLogin;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $strLastName
     * @return $this
     * @throws \Exception
     */
    public function setLastName(?string $strLastName): self
    {
        if (!($strLastName = trim($strLastName))) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_EMPTY, FieldValidateException::class);
        }

        if (strlen($strLastName) < 3) {
            ExceptionService::getException(ExceptionService::ENTITY_NAME_SHORT, FieldValidateException::class);
        }

        $this->lastName = $strLastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        return trim($this->lastName . ' ' . $this->name);
    }

    /**
     * @return string[]
     */
    public static function getAvailableRoles(): array
    {
        return [
            'ROLE_USER',
            'ROLE_ADMIN'
        ];
    }

    /**
     * @return array[]
     */
    public function getRoles(): array
    {
        $arRoles = explode(',', $this->roles);
        return count($arRoles) ? $arRoles : ['ROLE_USER'];
    }

    /**
     * @param array $arRoles
     * @return self
     */
    public function setRoles(array $arRoles): self
    {
        foreach ($arRoles as &$strRole) {
            $strRole = trim($strRole);
        }

        $arRoles     = array_filter($arRoles);
        $arRoles     = array_intersect($arRoles, self::getAvailableRoles());
        $this->roles = implode(',', $arRoles);

        return $this;
    }

    /**
     * @param string $strRoleName
     * @return boolean
     */
    public function isRole(string $strRoleName): bool
    {
        return in_array('ROLE_' . $strRoleName, $this->getRoles());
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return 'isSalt';
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getFullName();
    }

    /**
     * @return string[]
     */
    public function getAccessViews(): array
    {
        return $this->isRole('ADMIN') ?
            [
                'O', 'P', 'M'
            ] :
            ['O'];
    }

    /**
     * @return string[]
     */
    public function getAccessViewsMap(): array
    {
        return AccessProvider::ACCESS_STATUS;
    }

    /**
     * @return void
     */
    public function eraseCredentials()
    {
    }
}
