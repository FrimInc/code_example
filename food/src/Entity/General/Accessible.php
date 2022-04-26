<?php

namespace App\Entity\General;

use App\Entity\BaseEntity;
use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class Accessible extends BaseEntity
{

    public const VIEW_LINK   = '/';
    public const EDIT_LINK   = '/';
    public const DELETE_LINK = '/';

    /**
     * @var string
     *
     * @ORM\Column(name="ACCESS", type="string", nullable=true)
     */
    protected string $access;

    /**
     * @var TUsers $author
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TUsers")
     * @ORM\JoinColumn(name="AUTHOR", referencedColumnName="ID")
     */
    protected TUsers $author;

    private bool $canEdit    = false;
    private bool $canDelete  = false;
    private bool $canPublish = false;
    private bool $isMine     = false;
    private bool $isMineReal = false;

    /**
     * @var string
     */
    protected string $editLink = 'javascript:void(0);';

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
    public function getViewLink(): string
    {
        return str_replace('#id#', $this->getId(), static::VIEW_LINK);
    }

    /**
     * @return string
     */
    public function getEditLink(): string
    {
        return str_replace('#id#', $this->getId(), static::EDIT_LINK);
    }

    /**
     * @return string
     */
    public function getDeleteLink(): string
    {
        return str_replace('#id#', $this->getId(), static::DELETE_LINK);
    }

    /**
     * @param TUsers|UserInterface $obUser
     * @return self
     */
    public function makeRestrict(UserInterface $obUser): self
    {

        $this->canEdit    = $obUser->isRole('ADMIN') ||
            ($this->getAuthor()->getId() == $obUser->getId() && $this->getAccess() == 'P');
        $this->canDelete  = $this->canEdit;
        $this->canPublish = $this->getAccess() == 'P' && $this->getAuthor()->getId() == $obUser->getId() ||
            ($obUser->isRole('ADMIN') && $this->getAccess() != 'O');

        $this->isMine     = $obUser->getId() == $this->getAuthor()->getId() || $obUser->isRole('ADMIN');
        $this->isMineReal = $obUser->getId() == $this->getAuthor()->getId();

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsMine(): bool
    {
        return $this->isMine;
    }

    /**
     * @return bool
     */
    public function getIsMineReal(): bool
    {
        return $this->isMineReal;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $obUser
     * @return bool
     * @throws \Exception
     */
    public function checkCanDelete(UserInterface $obUser)
    {
        $this->makeRestrict($obUser);
        if (!$this->getCanDelete()) {
            ExceptionService::getException(ExceptionService::NO_ACCESS_EDIT);
        }
        return true;
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $obUser
     * @return bool
     * @throws \Exception
     */
    public function checkCanEdit(UserInterface $obUser): bool
    {
        $this->makeRestrict($obUser);
        if (!$this->getCanEdit()) {
            ExceptionService::getException(ExceptionService::NO_ACCESS_EDIT);
        }
        return true;
    }

    /**
     * @return bool|null
     */
    public function getCanEdit(): ?bool
    {
        return $this->canEdit;
    }

    /**
     * @return bool|null
     */
    public function getCanDelete(): ?bool
    {
        return $this->canDelete;
    }

    /**
     * @return bool|null
     */
    public function getCanPublish(): ?bool
    {
        return $this->canPublish;
    }
}
