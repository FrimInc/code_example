<?php

namespace App\Controller;

use App\Controller\Traits\AccessControllerTrait;
use App\Repository\MenuRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Menu extends PageController
{
    use AccessControllerTrait;

    protected MenuRepository $obMenuRepository;

    private Request $obRequest;

    /**
     * Menus constructor.
     *
     * @param MenuRepository $obMenuRepository
     */
    public function __construct(MenuRepository $obMenuRepository)
    {
        $this->obMenuRepository = $obMenuRepository;
        $this->obRequest        = Request::createFromGlobals();
        $this->obRepository     = $this->obMenuRepository;
    }

    /**
     * @Route("/app/menus")
     *
     * @return Response
     * @throws Exception
     */
    public function index(): Response
    {
        $arMenus = $this->obMenuRepository->getVisibleForUser($this->getTUser());
        foreach ($arMenus as $obMenu) {
            $obMenu->makeRestrict($this->getTUser());
        }
        return $this->json($arMenus);
    }

    /**
     * @param int $id
     *
     * @Route("/app/menu/{id<\d+>}")
     *
     * @return Response
     */
    public function view(int $id): Response
    {
        if ($id) {
            $obMenu = $this->obMenuRepository->getVisibleByID($id, $this->getTUser());
            if (!$obMenu) {
                return $this->returnError('Меню не найдено или доступ к нему запрещен');
            }
        } else {
            $obMenu = $this->obMenuRepository->getEmpty($this->getTUser());
        }

        $obMenu->makeRestrict($this->getTUser());

        return $this->json($obMenu);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/copy")
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
            $arResult['item'] = $this->obMenuRepository->put([
                'id'        => 0,
                'name'      => 'Копия ' . $obRequest->get('name'),
                'week'      => $obRequest->get('week'),
                'isCurrent' => false
            ], $this->getTUser())
                ->makeRestrict($this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/add")
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
            $arResult['item'] = $this->obMenuRepository->put([
                'id'   => $obRequest->get('id') ?: 0,
                'name' => $obRequest->get('name'),
                'week' => $obRequest->get('week')
            ], $this->getTUser())
                ->makeRestrict($this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/check")
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
            $arResult['item'] = $this->obMenuRepository->check([
                'id'   => $obRequest->get('id') ?: 0,
                'name' => $obRequest->get('name'),
                'week' => $obRequest->get('week')
            ], $this->getTUser())
                ->makeRestrict($this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/publish")
     *
     * @return Response
     */
    public function publish(Request $obRequest): Response
    {
        return $this->makePublish($obRequest);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/setCurrent")
     *
     * @return Response
     */
    public function setCurrent(Request $obRequest): Response
    {
        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $arResult['status']  = $this->obMenuRepository->setCurrent(
                $obRequest->get('id'),
                $this->getTUser()
            );
            $arResult['message'] = 'Назначено';
        } catch (Exception $eException) {
            return $this->returnException($eException);
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/menus/delete")
     *
     * @return Response
     */
    public function delete(Request $obRequest): Response
    {
        return $this->makeDelete($obRequest);
    }
}
