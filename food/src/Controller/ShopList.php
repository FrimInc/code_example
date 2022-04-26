<?php

namespace App\Controller;

use App\Controller\Traits\AccessControllerTrait;
use App\Exceptions\FieldValidateException;
use App\Repository\MenuRepository;
use App\Repository\ShopListRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopList extends PageController
{
    use AccessControllerTrait;

    protected ShopListRepository $shopListRepository;

    private Request $obRequest;

    /**
     * ShopLists constructor.
     *
     * @param ShopListRepository $shopListRepository
     */
    public function __construct(ShopListRepository $shopListRepository)
    {
        $this->shopListRepository = $shopListRepository;
        $this->obRequest          = Request::createFromGlobals();
        $this->obRepository       = $this->shopListRepository;
    }

    /**
     * @Route("/app/shopLists")
     *
     * @return Response
     * @throws Exception
     */
    public function index(): Response
    {
        $arShopLists = $this->shopListRepository->getVisibleForUser($this->getTUser());
        foreach ($arShopLists as $obShopList) {
            $obShopList->makeRestrict($this->getTUser());
        }
        return $this->json($arShopLists);
    }

    /**
     * @param int $id
     *
     * @Route("/app/shopList/{id<\d+>}")
     *
     * @return Response
     * @throws FieldValidateException
     */
    public function view(int $id): Response
    {
        if ($id) {
            $obShopList = $this->shopListRepository->getVisibleByID($id, $this->getTUser());
            if (!$obShopList) {
                return $this->returnError('Список покупок не найден или доступ к нему запрещен');
            }
        } else {
            $obShopList = $this->shopListRepository->getEmpty($this->getTUser());
        }

        $obShopList->makeRestrict($this->getTUser());

        return $this->json($obShopList);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/shopLists/add")
     *
     * @return Response
     */
    public function add(Request $obRequest): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $arResult['item'] = $this->shopListRepository->put([
                'id'   => $obRequest->get('id') ?: 0,
                'name' => $obRequest->get('name'),
                'list' => $obRequest->get('list')
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/shopLists/copy")
     *
     * @return Response
     */
    public function copy(Request $obRequest): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Скопировано'
        ];

        try {
            $arList = $obRequest->get('list');
            foreach ($arList['ingredients'] as &$arListIngredient) {
                $arListIngredient['isChecked'] = false;
            }

            $arResult['item'] = $this->shopListRepository->put([
                'id'   => 0,
                'name' => 'Копия ' . $obRequest->get('name'),
                'list' => $arList
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request        $obRequest
     * @param MenuRepository $obMenuRepository
     * @Route("/app/shopLists/createFromMenu")
     *
     * @return Response
     */
    public function createFromMenu(Request $obRequest, MenuRepository $obMenuRepository): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $intMenuId = $obRequest->get('id');

            $obMenu = $obMenuRepository->getVisibleByID($intMenuId, $this->getTUser());

            if (!$obMenu) {
                return $this->returnError('Список покупок не найден или доступ к нему запрещен');
            }

            $arResult['item'] = $this->shopListRepository->put([
                'name' => $obRequest->get('name'),
                'list' => [
                    'ingredients' => $obMenu->getIngredients()
                ]
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request        $obRequest
     * @param MenuRepository $obMenuRepository
     * @Route("/app/shopLists/addToMainFromMenu")
     *
     * @return Response
     */
    public function addToMainFromMenu(Request $obRequest, MenuRepository $obMenuRepository): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $intMenuId = $obRequest->get('id');

            $obMenu = $obMenuRepository->getVisibleByID($intMenuId, $this->getTUser());

            if (!$obMenu) {
                return $this->returnError('Список покупок не найден или доступ к нему запрещен');
            }

            $arResult['item'] = $this->shopListRepository->put([
                'name'  => $obRequest->get('name'),
                'list'  => [
                    'ingredients' => $obMenu->getIngredients()
                ],
                'main'  => true,
                'merge' => true
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/shopLists/check")
     *
     * @return Response
     */
    public function check(Request $obRequest): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Пересчитано'
        ];

        try {
            $arResult['item'] = $this->shopListRepository->check([
                'id'   => $obRequest->get('id') ?: 0,
                'name' => $obRequest->get('name'),
                'list' => $obRequest->get('list')
            ], $this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/shopLists/delete")
     *
     * @return Response
     */
    public function delete(Request $obRequest): Response
    {
        return $this->makeDelete($obRequest);
    }


    /**
     * @param Request $obRequest
     * @Route("/app/shopLists/setMain")
     *
     * @return Response
     */
    public function setMain(Request $obRequest): Response
    {
        $arResult = [
            'status'  => true,
            'message' => 'Пересчитано'
        ];

        try {
            $arResult['status'] = $this->shopListRepository->setMain(
                $obRequest->get('id'),
                $this->getTUser()
            );
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }
}
