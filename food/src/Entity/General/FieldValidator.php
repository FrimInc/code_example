<?php

namespace App\Entity\General;

use App\Exceptions\ExceptionService;
use App\Exceptions\FieldValidateException;

class FieldValidator
{

    private $value;

    /**
     * FieldValidator constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * FieldValidator constructor.
     *
     * @param $value
     * @return self
     */
    public static function v($value): self
    {
        return new self($value);
    }

    /**
     * @param        $from
     * @param        $to
     * @param ?array $arExceptionData
     * @param string $strException
     * @return self
     * @throws \Exception
     */
    public function validateRange(
        $from = PHP_INT_MIN,
        $to = PHP_INT_MAX,
        ?array $arExceptionData = null,
        $strException = FieldValidateException::class
        //@formatter:off
    ): self {
        //@formatter:on
        if ($this->value < $from || $this->value > $to) {
            ExceptionService::getException(
                $arExceptionData ?: ExceptionService::RANGE_ERROR,
                $strException,
                ": $from < $this->value < $to ?"
            );
        }

        return $this;
    }

    /**
     * @param ?array $arExceptionData
     * @param string $strException
     * @return self
     *
     * @throws \Exception
     */
    public function validateRound(?array $arExceptionData = null, $strException = FieldValidateException::class)
    {
        if (!is_int($this->value)) {
            ExceptionService::getException(
                $arExceptionData ?: ExceptionService::DIGIT_ERROR,
                $strException,
                ": $this->value"
            );
        }

        return $this;
    }
}
