<?php

namespace App\Entity;

use App\Entity\Annotations\SkipInJson;
use Doctrine\Common\Annotations\AnnotationReader as DocReader;
use Doctrine\Common\Util\ClassUtils;
use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use Doctrine\ORM\Mapping as ORM;

abstract class BaseEntity implements JsonSerializable
{

    /**
     * @var int
     *
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $intId
     * @return self
     */
    public function setId(int $intId): self
    {
        $this->id = $intId;
        return $this;
    }

    /**
     * @return array|object
     */
    public function jsonSerialize()
    {
        $arReturn = [];
        $obReader = new DocReader();

        static $arCaches = [];
        $strCacheName = ClassUtils::getClass($this) . '_' . $this->id;

        if (array_key_exists($strCacheName, $arCaches)) {
            return $arCaches[$strCacheName];
        }

        try {
            $obReflector = new ReflectionClass(ClassUtils::getClass($this));
        } catch (ReflectionException $obException) {
            return $this;
        }

        $arCalledGetters = [];

        foreach ($obReflector->getProperties() as $obPropertyRef) {
            if (method_exists($this, 'get' . $obPropertyRef->getName())) {
                $arCalledGetters[strtolower($strGetterName = 'get' . $obPropertyRef->getName())] = true;
                if (!$obReader->getPropertyAnnotation($obPropertyRef, SkipInJson::class)) {
                    $arReturn[$obPropertyRef->getName()] = $this->{$strGetterName}();
                }
            }
        }

        foreach ($obReflector->getMethods() as $obMethodRef) {
            if (substr($obMethodRef->name, 0, 3) === 'get') {
                if (!array_key_exists(strtolower($obMethodRef->name), $arCalledGetters)) {
                    $arReturn[lcfirst(str_replace('get', '', $obMethodRef->name))] = $this->{$obMethodRef->name}();
                }
            }
        }

        return ($arCaches[$strCacheName] = $arReturn);
    }
}
