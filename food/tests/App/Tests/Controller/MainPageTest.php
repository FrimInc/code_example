<?php

namespace App\Tests\Controller;

use App\Entity\TUsers;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class MainPageTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private static $entityManager;

    private static UserRepository $obUserRepository;
    private static ?TUsers        $obRegularUser;

    private KernelBrowser $client;

    /**
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();

        if (!self::$entityManager) {
            self::$entityManager = $this->client->getContainer()
                ->get('doctrine')
                ->getManager();

            self::$obUserRepository = self::$entityManager->getRepository(TUsers::class);
        }

        self::$obRegularUser = self::$obUserRepository->findOneByRole('ROLE_USER');

        $this->logIn(self::$obRegularUser);
    }

    /**
     * @param \Symfony\Component\Security\Core\User\UserInterface $obUser
     * @return void
     */
    private function logIn(UserInterface $obUser)
    {
        $obSession = $this->client->getContainer()->get('session');

        $strFirewallName    = 'main';
        $strFirewallContext = 'main';

        $obToken = new UsernamePasswordToken($obUser, null, $strFirewallName, $obUser->getRoles());
        $obSession->set('_security_' . $strFirewallContext, serialize($obToken));
        $obSession->save();

        $obCookie = new Cookie($obSession->getName(), $obSession->getId());
        $this->client->getCookieJar()->set($obCookie);
    }

    /**
     * @return void
     */
    public function testApp(): void
    {
        $this->client->request('GET', '/main');
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertArrayHasKey('user', $arResponseData);
        $this->assertArrayHasKey('units', $arResponseData);
        $this->assertArrayHasKey('type', $arResponseData);
        $this->assertArrayHasKey('ingredientType', $arResponseData);
        $this->assertArrayHasKey('top_menu', $arResponseData);

        $this->assertSame($arResponseData['user']['id'], self::$obRegularUser->getId());
        $this->assertNotCount(0, $arResponseData['units']);
        $this->assertNotCount(0, $arResponseData['type']);
        $this->assertNotCount(0, $arResponseData['ingredientType']);
        $this->assertNotCount(0, $arResponseData['top_menu']);
    }

    /**
     * @return void
     */
    public function testMainPage(): void
    {
        $this->client->request('GET', '/app/mainPage');
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertArrayHasKey('menu', $arResponseData);
        $this->assertArrayHasKey('shopLists', $arResponseData);
        $this->assertIsArray($arResponseData['shopLists']);
        $this->assertArrayHasKey('day', $arResponseData);
    }
}
