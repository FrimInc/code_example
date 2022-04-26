<?php

namespace App\Controller;

use App\Repository\TypeRepository;
use App\Tools\ArrayObjectTools;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Type extends PageController
{
    private TypeRepository $typeRepository;
    private Request        $obRequest;

    /**
     * Types constructor.
     *
     * @param TypeRepository $typeRepository
     */
    public function __construct(TypeRepository $typeRepository)
    {
        $this->typeRepository = $typeRepository;
        $this->obRequest      = Request::createFromGlobals();
    }


    /**
     * @Route("/app/autocomplete/Type")
     *
     * @return Response
     * @throws Exception
     */
    public function autocomplete(): Response
    {

        $arTypes = ArrayObjectTools::getArrayOfObjectField(
            $this->typeRepository->findByName('%' . $this->obRequest->request->get('search') . '%')
        );

        return new Response(json_encode(array_values($arTypes)));
    }
}
