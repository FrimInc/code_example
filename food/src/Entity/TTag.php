<?php

namespace App\Entity;

use App\Entity\General\Accessible;
use App\Entity\Traits\NameTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TTag
 *
 * @ORM\Table(name="t_tag")
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 */
class TTag extends Accessible
{
    use NameTrait;
}
