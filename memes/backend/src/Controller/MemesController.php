<?php

namespace App\Controller;

use App\Controller\Interfaces\MemesInterface;
use App\Entity\Request\PictureAddRequest;
use App\Services\MemesPosterService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/picture")
 */
class MemesController implements MemesInterface
{
    private MemesPosterService $memesService;

    public function __construct(MemesPosterService $memesService)
    {
        $this->memesService = $memesService;
    }

    /**
     * @Route("/add/", methods={"POST"})
     */
    public function addAction(PictureAddRequest $addPictureRequest): Response
    {
        return new JsonResponse(
            [
                'success' => true,
                'data'    => [
                    'path' => $this->memesService->saveMeme($addPictureRequest->getImg())
                ]
            ]
        );
    }

    /**
     * @Route("/post/", methods={"POST"})
     */
    public function postAction(PictureAddRequest $addPictureRequest): Response
    {
        $filePath = $this->memesService->saveMeme($addPictureRequest->getImg());
        $this->memesService->postOne($filePath);

        return new JsonResponse(
            [
                'success' => true,
                'data'    => [
                    'path' => $filePath
                ]
            ]
        );
    }
}