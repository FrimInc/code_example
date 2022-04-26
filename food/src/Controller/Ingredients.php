<?php

namespace App\Controller;

use App\Controller\Traits\AccessControllerTrait;
use App\Repository\IngredientRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Ingredients extends PageController
{
    use AccessControllerTrait;

    protected ?IngredientRepository $obIngredientRepository = null;

    /**
     * Ingredients constructor.
     *
     * @param IngredientRepository $ingredientRepository
     */
    public function __construct(IngredientRepository $ingredientRepository)
    {
        $this->obIngredientRepository = $this->obIngredientRepository ?: $ingredientRepository;
        $this->obRepository           = $this->obIngredientRepository;
    }

    /**
     * @Route("/app/ingredients")
     * @param Request $obRequest
     * @return Response
     * @throws Exception
     */
    public function index(Request $obRequest): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $arIngredients = $this
            ->obIngredientRepository
            ->getVisibleForUser(
                $this->getTUser(),
                $obRequest->getContent() ? $obRequest->toArray() : $obRequest->request->all()
            );

        foreach ($arIngredients as $obIngredient) {
            $obIngredient->makeRestrict($this->getTUser());
        }

        return $this->json($arIngredients);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/ingredients/add")
     *
     * @return Response
     */
    public function add(Request $obRequest): Response
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $arResult['item'] = $this->obIngredientRepository->put([
                'id'      => $obRequest->get('id') ?: 0,
                'name'    => $obRequest->get('name'),
                'units'   => $obRequest->get('units'),
                'type'    => $obRequest->get('ingredientType'),
                'minimum' => $obRequest->get('minimum')
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException->getMessage();
            $arResult['code']    = $eException->getCode();
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/ingredients/publish")
     *
     * @return Response
     */
    public function publish(Request $obRequest): Response
    {
        return $this->makePublish($obRequest);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/ingredients/delete")
     *
     * @return Response
     */
    public function delete(Request $obRequest): Response
    {
        return $this->makeDelete($obRequest);
    }

    /**
     * @param Request $obRequest
     *
     * @Route("/app/autocomplete/ingredient")
     * @return Response
     */
    public function autocomplete(Request $obRequest): Response
    {

        $arIngredients = $this->obIngredientRepository->findByName(
            '%' . $obRequest->get('search') . '%',
            $this->getTUser()
        );

        return $this->json($arIngredients);
    }
}
