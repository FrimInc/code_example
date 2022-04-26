<?php

namespace App\Services;

use VK\Client\VKApiClient;
use VK\Exceptions\Api\ExceptionMapper;
use VK\Exceptions\Api\VKApiParamException;

class VkService
{
    private VKApiClient $vk;
    private string $accessToken;

    public function __construct()
    {
        $this->vk = new VKApiClient('5.131');
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): VkService
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * @throws \VK\Exceptions\Api\VKApiWallTooManyRecipientsException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPublishedException
     * @throws \VK\Exceptions\Api\VKApiWallAdsPostLimitReachedException
     * @throws \VK\Exceptions\Api\VKApiWallLinksForbiddenException
     * @throws \VK\Exceptions\VKClientException
     * @throws \VK\Exceptions\Api\VKApiWallAddPostException
     * @throws \VK\Exceptions\VKApiException
     */
    public function postMeme(string $filePath, int $groupId): bool
    {
        try {
            $server = $this->vk->photos()->getWallUploadServer($this->accessToken);
            $photo  = $this->vk->getRequest()->upload($server['upload_url'], 'photo', $filePath);

            $photoSaveResult = $this->vk->photos()->saveWallPhoto(
                $this->accessToken,
                [
                    'groupId'    => $groupId,
                    'from_group' => 0,
                    'photo'      => $photo['photo'],
                    'server'     => $photo['server'],
                    'hash'       => $photo['hash']
                ]
            )[0];
            if (!$photoSaveResult['id']) {
                return false;
            }

            $result = $this->vk->wall()->post(
                $this->accessToken,
                [
                    'owner_id'    => $groupId * -1,
                    'from_group'  => 1,
                    'attachments' => 'photo' . $photoSaveResult['owner_id'] . '_' . $photoSaveResult['id']
                ]
            );

            return (bool)$result['post_id'];
        } catch (VKApiParamException $e) {
            return true;
        }
    }

}