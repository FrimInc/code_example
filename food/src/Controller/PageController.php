<?php

namespace App\Controller;

use App\Entity\TIngredientType;
use App\Entity\TShopList;
use App\Entity\TType;
use App\Entity\TUnits;
use App\Entity\TUsers;
use App\Helpers\Logger;
use App\Navigation\Menu;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController implements LoggerAwareInterface
{

    public ?TUsers                $obUser     = null;
    public static ?PageController $obInstance = null;

    /**
     * PageController constructor.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        Logger::setLogger($logger);
    }

    /**
     * @return PageController
     */
    public static function getInstance(): PageController
    {

        if (self::$obInstance == null) {
            self::$obInstance = new PageController();
        }
        return self::$obInstance;
    }

    /**
     * @return TUsers
     */
    protected function getTUser(): TUsers
    {
        if ($this->obUser == null) {
            $this->obUser = $this->getUser();
        }

        return $this->obUser;
    }

    /**
     * @Route("/main")
     *
     * @return Response
     */
    public function app(): Response
    {
        $arAppVars = [
            'user'            => null,
            'top_menu'        => [],
            'title'           => 'Все рецепты',
            'token'           => '',
            'unit'            => [],
            'type'            => [],
            'hasMainShopList' => false
        ];

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($arAppVars['user'] = $this->getTUser()) {
            $obMenu = new Menu();

            $arAppVars['top_menu'] = $obMenu->getMenu('top', $this->getParameter('kernel.project_dir'));

            $obEntityManager = $this->getDoctrine()->getManager();

            $obUnitRepository           = $obEntityManager->getRepository(TUnits::class);
            $obIngredientTypeRepository = $obEntityManager->getRepository(TIngredientType::class);
            $obTypeRepository           = $obEntityManager->getRepository(TType::class);
            $obShopListRepository       = $obEntityManager->getRepository(TShopList::class);

            $arAppVars['units']          = $obUnitRepository->findAll();
            $arAppVars['type']           = $obTypeRepository->findBy([], ['parent' => 'ASC', 'id' => 'asc']);
            $arAppVars['ingredientType'] = $obIngredientTypeRepository->findBy([], ['sort' => 'asc']);

            $arMainShoplists = $obShopListRepository->getOwnedByUser(
                $arAppVars['user'],
                ['filter' => ['main' => true]]
            );

            $arAppVars['hasMainShopList'] = count($arMainShoplists) > 0;
        }

        return $this->json($arAppVars);
    }

    /**
     * @param string $errorText
     * @return JsonResponse
     */
    public function returnError(string $errorText)
    {
        return $this->json([
            'status'  => false,
            'message' => $errorText
        ]);
    }

    /**
     * @param Exception $eException
     * @return JsonResponse
     */
    public function returnException(Exception $eException)
    {
        return $this->json([
            'status'  => false,
            'message' => $eException->getMessage() . (
                $_SERVER['APP_ENV'] === 'dev'
                    ? $eException->getTraceAsString()
                    : ''
                ),
            'code'    => $eException->getCode()
        ]);
    }
}
