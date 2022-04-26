<?php

namespace App\Controller\Traits;

use App\Repository\General\AccessProvider;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait AccessControllerTrait
{
    public ?AccessProvider $obRepository = null;

    /**
     * @param \Symfony\Component\HttpFoundation\Request $obRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function makePublish(Request $obRequest): Response
    {
        try {
            return $this->json([
                'status'  => true,
                'message' => 'Опубликовано',
                'item'    => $this->obRepository->makePublish($obRequest->get('id'), $this->getTUser())
            ]);
        } catch (Exception $eException) {
            return $this->returnException($eException);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $obRequest
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function makeDelete(Request $obRequest)
    {
        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $arResult['status']  = $this->obRepository->delete($obRequest->get('id'), $this->getTUser());
            $arResult['message'] = 'Удалено';
        } catch (Exception $eException) {
            return $this->returnException($eException);
        }

        return $this->json($arResult);
    }
}
