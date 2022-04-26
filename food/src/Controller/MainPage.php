<?php

namespace App\Controller;

use App\Entity\TMenu;
use App\Entity\TShopList;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainPage extends PageController
{
    /**
     * @Route("/")
     * @Route("/{page}")
     * @Route("/{page}/{id<\d+>}")
     * @Route("/{page1}/{id<\d+>}/{page2}")
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render(
            'base.html.twig'
        );
    }

    /**
     * @Route("/app/mainPage")
     *
     * @return Response
     */
    public function mainPage(): Response
    {
        $arResult = [
            'menu'      => false,
            'shopLists' => [],
            'day'       => date('N') - 1
        ];

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $obEntityManager    = $this->getDoctrine()->getManager();
        $obMenuRepository   = $obEntityManager->getRepository(TMenu::class);
        $shopListRepository = $obEntityManager->getRepository(TShopList::class);

        if (count($arMenus = $obMenuRepository->getCurrentForUser($this->getTUser()))) {
            $arResult['menu'] = $arMenus[0]->getId();
        }

        foreach ($shopListRepository->getOwnedByUser($this->getTUser()) as $arShopList) {
            if (count($arShopList->getGroupedList(false)) || $arShopList->getMain()) {
                $arResult['shopLists'][] = $arShopList->getId();
            }
        }


        return $this->json($arResult);
    }
}
