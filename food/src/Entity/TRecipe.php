<?php

namespace App\Entity;

use App\Entity\General\Accessible;
use App\Entity\General\FieldValidator;
use App\Entity\Traits\NameTrait;
use App\Exceptions\ExceptionService;
use App\Exceptions\FieldValidateException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * TRecipe
 *
 * @ORM\Table(name="t_recipe", indexes={@ORM\Index(name="idx_ttime", columns={"TOTAL_TIME"}),
 * @ORM\Index(name="idx_type", columns={"TYPE"}), @ORM\Index(name="idx_name", columns={"NAME"}),
 * @ORM\Index(name="idx_serving", columns={"SERVING"}), @ORM\Index(name="idx_atime", columns={"ACTIVE_TIME"})})
 * @ORM\Entity(repositoryClass="App\Repository\RecipeRepository")
 */
class TRecipe extends Accessible
{
    use NameTrait;

    public const VIEW_LINK   = '/recipe/#id#';
    public const EDIT_LINK   = '/recipe/#id#/edit';
    public const DELETE_LINK = '/app/recipes/delete';

    /**
     * @var string
     *
     * @ORM\Column(name="DESCRIPTION", type="text", length=0, nullable=false)
     */
    private string $description = '[\' \']';

    /**
     * @var string
     *
     * @ORM\Column(name="ANOUNCE", type="text", length=0, nullable=true)
     */
    private string $anounce = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="XMLID", type="text", length=0, nullable=true)
     */
    private ?string $xmlid = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="PICS", type="string", length=255, nullable=true)
     */
    private string $pics;

    /**
     * @var int
     *
     * @ORM\Column(name="SERVING", type="integer", nullable=false)
     */
    private int $serving = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="DAYS", type="integer", nullable=true)
     */
    private int $days = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="DIFFICULT", type="integer", nullable=false)
     */
    private int $difficult = 3;

    /**
     * @var TType
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TType")
     * @ORM\JoinColumn(name="TYPE", referencedColumnName="ID")
     */
    private TType $type;

    /**
     * @var PersistentCollection
     * @ORM\OneToMany(targetEntity="TRecipeIngredient", mappedBy="recipe")
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    private PersistentCollection $ingredients;

    /**
     * @var PersistentCollection
     * @ORM\OneToMany(targetEntity="TRecipeTag", mappedBy="recipe")
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    private PersistentCollection $tags;

    /**
     * @var int
     *
     * @ORM\Column(name="ACTIVE_TIME", type="integer", nullable=true)
     */
    private int $activeTime = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="TOTAL_TIME", type="integer", nullable=true)
     */
    private int $totalTime = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="KKAL", type="integer", nullable=true)
     */
    private int $kkal = 0;

    /**
     * @param bool $force
     * @return string|null
     */
    public function getDescription(bool $force = false): ?string
    {
        return $force ? $this->description : '';
    }

    /**
     * @param $strNewDescription
     * @return $this
     */
    public function setDescription($strNewDescription): self
    {
        $this->description = $strNewDescription;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnounce(): ?string
    {
        return $this->anounce;
    }

    /**
     * @param string $strNewAnounce
     * @return $this
     * @throws FieldValidateException
     * @throws \Exception
     */
    public function setAnounce(string $strNewAnounce): self
    {
        if (!($strNewAnounce = trim($strNewAnounce))) {
            ExceptionService::getException(ExceptionService::RECIPE_ANNOUNCE_EMPTY, FieldValidateException::class);
        }

        $this->anounce = $strNewAnounce;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getXmlid(): ?string
    {
        return $this->xmlid;
    }

    /**
     * @param string|null $xmlid
     * @return $this
     */
    public function setXmlid(?string $xmlid): self
    {
        $this->xmlid = $xmlid;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getStages(): array
    {
        return array_values(json_decode($this->description, true) ?: ['']);
    }

    /**
     * @param array $arStages
     * @return self
     * @throws \App\Exceptions\FieldValidateException
     * @throws \Exception
     */
    public function setStages(array $arStages): self
    {
        foreach ($arStages as &$strStage) {
            $strStage = trim($strStage);
        }

        $arStages = array_filter($arStages);

        if (!count($arStages)) {
            ExceptionService::getException(ExceptionService::RECIPE_STAGES_EMPTY, FieldValidateException::class);
        }

        $arStages = array_values($arStages);

        $this->description = json_encode($arStages);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPics(): ?string
    {
        return $this->pics;
    }

    /**
     * @param string $pics
     * @return $this
     */
    public function setPics(string $pics): self
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getServing(): ?int
    {
        return $this->serving;
    }

    /**
     * @param int $serving
     * @return $this
     * @throws \App\Exceptions\FieldValidateException
     */
    public function setServing(int $serving): self
    {
        FieldValidator::v($serving)->validateRange(1);
        $this->serving = $serving;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDays(): ?int
    {
        return $this->days;
    }

    /**
     * @param int $days
     * @return $this
     * @throws \App\Exceptions\FieldValidateException
     */
    public function setDays(int $days): self
    {
        FieldValidator::v($days)->validateRound()->validateRange(0, 20);
        $this->days = $days;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDifficult(): ?int
    {
        return $this->difficult;
    }

    /**
     * @param int $difficult
     * @return $this
     * @throws \Exception
     */
    public function setDifficult(int $difficult): self
    {

        FieldValidator::v($difficult)->validateRange(1, 5, ExceptionService::RECIPE_DIFFICULTY_WRONG);

        $this->difficult = $difficult;

        return $this;
    }

    /**
     * @return TType
     */
    public function getType(): TType
    {
        return $this->type;
    }

    /**
     * @param TType $type
     * @return $this
     */
    public function setType(TType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getActiveTime(): int
    {
        return $this->activeTime;
    }

    /**
     * @param int $activeTime
     * @return $this
     */
    public function setActiveTime(int $activeTime): self
    {
        FieldValidator::v($activeTime)->validateRange(0, 3000);
        $this->activeTime = $activeTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalTime(): int
    {
        return $this->totalTime;
    }

    /**
     * @param int $totalTime
     * @return $this
     */
    public function setTotalTime(int $totalTime): self
    {
        FieldValidator::v($totalTime)->validateRange(0, 100000);
        $this->totalTime = $totalTime;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getKkal(): ?int
    {
        return $this->kkal;
    }

    /**
     * @param int $kkal
     * @return $this
     */
    public function setKkal(int $kkal): self
    {
        FieldValidator::v($kkal)->validateRange(0, 5000);
        $this->kkal = $kkal;

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getIngredients(): PersistentCollection
    {
        return $this->ingredients;
    }

    /**
     * @param PersistentCollection $ingredients
     * @return $this
     */
    public function setIngredients(PersistentCollection $ingredients): self
    {
        $this->ingredients = $ingredients;

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getTags(): PersistentCollection
    {
        return $this->tags;
    }

    /**
     * @param PersistentCollection $tags
     * @return $this
     */
    public function setTags(PersistentCollection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }
}
