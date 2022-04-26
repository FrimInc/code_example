<?php

namespace App\Entity\Request;

class PictureAddRequest
{

    private string $img;

    /**
     * @param string $img
     */
    public function __construct(string $img)
    {
        $this->img = $img;
    }

    /**
     * @return string
     */
    public function getImg(): string
    {
        return $this->img;
    }

    /**
     * @param string $img
     * @return PictureAddRequest
     */
    public function setImg(string $img): PictureAddRequest
    {
        $this->img = $img;
        return $this;
    }
}