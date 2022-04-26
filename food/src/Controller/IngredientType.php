<?php

namespace App\Controller;

use App\Repository\IngredientTypeRepository;
use App\Tools\ArrayObjectTools;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IngredientType extends PageController
{
    private IngredientTypeRepository $obIngredientTypeRepository;

    private Request $obRequest;

    /**
     * Units constructor.
     *
     * @param IngredientTypeRepository $obIngredientTypeRepository
     */
    public function __construct(IngredientTypeRepository $obIngredientTypeRepository)
    {
        $this->obIngredientTypeRepository = $obIngredientTypeRepository;
        $this->obRequest                  = Request::createFromGlobals();
    }

    /**
     * @Route("/app/autocomplete/ingredientType")
     *
     * @return Response
     * @throws Exception
     */
    public function autocomplete(): Response
    {

        $arIngredients = ArrayObjectTools::getArrayOfObjectField(
            $this->obIngredientTypeRepository->findByName('%' . $this->obRequest->request->get('search') . '%')
        );

        return new Response(json_encode(array_values($arIngredients)));
    }
}
