<?php

namespace App\Services;

use App\Exceptions\MemesSaveImageException;

class MemesPosterService
{
    private string $queryDir;
    private string $doneDir;
    private VkService $vkService;

    /**
     * @throws \App\Exceptions\MemesSaveImageException
     */
    public function __construct(VkService $vkService)
    {
        $this->queryDir  = getenv('IMAGE_PATH') . 'query/';
        $this->doneDir   = getenv('IMAGE_PATH') . 'done/';
        $this->vkService = $vkService->setAccessToken(getenv('TOKEN'));

        if (!is_dir($this->queryDir) && !mkdir($this->queryDir)) {
            throw new MemesSaveImageException(sprintf('Directory "%s" was not created', $this->queryDir));
        }
        if (!is_dir($this->doneDir) && !mkdir($this->doneDir)) {
            throw new MemesSaveImageException(sprintf('Directory "%s" was not created', $this->doneDir));
        }
    }

    /**
     * @throws \App\Exceptions\MemesSaveImageException
     */
    public function saveMeme(string $imgUrl): string
    {
        $content = file_get_contents($imgUrl);
        if (!$content) {
            throw new MemesSaveImageException('Пустое изображение');
        }

        $filePath = $this->queryDir . date('d_m_Y_H_I_s_') . basename($imgUrl);

        file_put_contents($filePath, $content);
        return $filePath;
    }

    /**
     * @throws \VK\Exceptions\Api\VKApiWallTooManyRecipientsException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPublishedException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPostLimitReachedException
     * @throws \VK\Exceptions\VKClientException
     * @throws \VK\Exceptions\Api\VKApiWallLinksForbiddenException
     * @throws \VK\Exceptions\Api\VKApiWallAddPostException
     * @throws \VK\Exceptions\VKApiException
     */
    public function postOne(string $fileName): bool
    {
        if ($this->vkService->postMeme($fileName, getenv('GROUP_ID'))) {
            $this->moveToDone($fileName);
        }
        return $fileName;
    }

    /**
     * @throws \VK\Exceptions\Api\VKApiWallTooManyRecipientsException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPublishedException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPostLimitReachedException
     * @throws \VK\Exceptions\VKClientException
     * @throws \VK\Exceptions\Api\VKApiWallLinksForbiddenException
     * @throws \VK\Exceptions\Api\VKApiWallAddPostException
     * @throws \VK\Exceptions\VKApiException
     */
    public function postOneFromQuery(): bool
    {
        $fileName = $this->getNext();
        if ($this->vkService->postMeme($fileName, getenv('GROUP_ID'))) {
            $this->moveToDone($fileName);
        }
        return $fileName;
    }

    private function getNext(): string
    {
        $arQuery = array_diff(
            scandir($this->queryDir),
            array('..', '.')
        );

        return count($arQuery) ? $this->queryDir . array_shift(
                $arQuery
            ) : '';
    }

    private function moveToDone(string $fileName): void
    {
        rename($fileName, $this->doneDir . basename($fileName));
    }

}