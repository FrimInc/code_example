<?php

namespace App\Controller;

use App\Controller\Traits\AccessControllerTrait;
use App\Repository\TagRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Tags extends PageController
{
    use AccessControllerTrait;

    protected ?TagRepository $obTagRepository = null;

    /**
     * Tags constructor.
     *
     * @param TagRepository $ingredientRepository
     */
    public function __construct(TagRepository $ingredientRepository)
    {
        $this->obTagRepository = $this->obTagRepository ?: $ingredientRepository;
        $this->obRepository           = $this->obTagRepository;
    }

//    /**
//     * @Route("/app/ingredients")
//     * @param Request $obRequest
//     * @return Response
//     * @throws Exception
//     */
//    public function index(Request $obRequest): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_USER');
//
//        $arTags = $this
//            ->obTagRepository
//            ->getVisibleForUser(
//                $this->getTUser(),
//                $obRequest->getContent() ? $obRequest->toArray() : $obRequest->request->all()
//            );
//
//        foreach ($arTags as $obTag) {
//            $obTag->makeRestrict($this->getTUser());
//        }
//
//        return $this->json($arTags);
//    }
//
//    /**
//     * @param Request $obRequest
//     * @Route("/app/ingredients/add")
//     *
//     * @return Response
//     */
//    public function add(Request $obRequest): Response
//    {
//
//        $this->denyAccessUnlessGranted('ROLE_USER');
//
//        $arResult = [
//            'status'  => true,
//            'message' => 'Сохранено'
//        ];
//
//        try {
//            $arResult['item'] = $this->obTagRepository->put([
//                'id'      => $obRequest->get('id') ?: 0,
//                'name'    => $obRequest->get('name'),
//                'units'   => $obRequest->get('units'),
//                'type'    => $obRequest->get('ingredientType'),
//                'minimum' => $obRequest->get('minimum')
//            ], $this->getTUser());
//        } catch (Exception $eException) {
//            $arResult['status']  = false;
//            $arResult['message'] = $eException->getMessage();
//            $arResult['code']    = $eException->getCode();
//        }
//
//        return $this->json($arResult);
//    }
//
//    /**
//     * @param Request $obRequest
//     * @Route("/app/ingredients/publish")
//     *
//     * @return Response
//     */
//    public function publish(Request $obRequest): Response
//    {
//        return $this->makePublish($obRequest);
//    }
//
//    /**
//     * @param Request $obRequest
//     * @Route("/app/ingredients/delete")
//     *
//     * @return Response
//     */
//    public function delete(Request $obRequest): Response
//    {
//        return $this->makeDelete($obRequest);
//    }

    /**
     * @param Request $obRequest
     *
     * @Route("/app/autocomplete/tags")
     * @return Response
     */
    public function autocomplete(Request $obRequest): Response
    {

        $arTags = $this->obTagRepository->findByName(
            '%' . $obRequest->get('search') . '%',
            $this->getTUser()
        );

        return $this->json($arTags);
    }
}
