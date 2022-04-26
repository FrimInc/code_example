<?php

namespace App\Controller;

use App\Repository\UnitRepository;

class Units extends PageController
{
    private UnitRepository $unitRepository;

    /**
     * Units constructor.
     *
     * @param UnitRepository $unitRepository
     */
    public function __construct(UnitRepository $unitRepository)
    {
        $this->unitRepository = $unitRepository;
    }
}
