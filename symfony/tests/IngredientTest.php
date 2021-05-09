<?php

namespace App\Tests\Controller;

use App\Entity\TIngredient;
use App\Entity\TIngredientType;
use App\Entity\TUnits;
use App\Entity\TUsers;
use App\Exceptions\ExceptionFactory;
use App\Repository\IngredientRepository;
use App\Repository\IngredientTypeRepository;
use App\Repository\UnitRepository;
use App\Repository\UserRepository;
use App\Tests\Helpers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class IngredientTest extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private static $entityManager;

    private static array                    $arValidIngredient = [];
    private static IngredientRepository     $obIngredientRepository;
    private static UserRepository           $obUserRepository;
    private static UnitRepository           $obUnitRepository;
    private static IngredientTypeRepository $obTypeRepository;

    private static ?TUsers $obAdminUser;
    private static ?TUsers $obRegularUser;

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

            self::$obIngredientRepository = self::$entityManager->getRepository(TIngredient::class);
            self::$obUnitRepository       = self::$entityManager->getRepository(TUnits::class);
            self::$obUserRepository       = self::$entityManager->getRepository(TUsers::class);
            self::$obTypeRepository       = self::$entityManager->getRepository(TIngredientType::class);
        }

        self::$obAdminUser   = self::$obUserRepository->findOneByRole('%ROLE_ADMIN%');
        self::$obRegularUser = self::$obUserRepository->findOneByRole('ROLE_USER');

        $obType                  = self::$obTypeRepository->findAll()[0];
        $obUnit                  = self::$obUnitRepository->findAll()[0];
        self::$arValidIngredient = [
            'name'    => Helpers::getRandString(),
            'units'   => $obUnit->getId(),
            'type'    => $obType->getId(),
            'minimum' => 1
        ];

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
    public function testIngredientList(): void
    {
        $this->client->request('GET', '/app/ingredients');
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertArrayHasKey(0, $arResponseData);
        $this->assertArrayHasKey('id', $arResponseData[0]);
        $this->assertArrayHasKey('name', $arResponseData[0]);
    }

    /**
     * @return void
     */
    public function testIngredientPut(): void
    {
        $this->client->request('POST', '/app/ingredients/add', self::$arValidIngredient);

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $strContent = $obResponse->getContent();
        $this->assertJson($strContent);
        $arResponseData = json_decode($strContent, true);

        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);
    }

    /**
     * @return void
     */
    public function testIngredientPutExists(): void
    {
        $obExistsIngredient = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser)[0];

        $this->client->request(
            'POST',
            '/app/ingredients/add',
            array_merge(
                self::$arValidIngredient,
                ['name' => $obExistsIngredient->getName()]
            )
        );
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(false, $arResponseData['status']);
        $this->assertSame(ExceptionFactory::INGREDIENT_NAME_EXISTS['code'], $arResponseData['code']);
    }

    /**
     * @return void
     * @depends testIngredientPut
     */
    public function testIngredientPublish(): void
    {

        $obExistsIngredient = self::$obIngredientRepository->findMyUnmoderated(self::$obRegularUser)[0];

        $this->client->request(
            'POST',
            '/app/ingredients/publish',
            [
                'id' => $obExistsIngredient->getId()
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);
    }

    /**
     * @return void
     * @depends testIngredientPut
     */
    public function testIngredientPublishError(): void
    {

        $obExistsIngredient = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser)[0];

        $this->client->request(
            'POST',
            '/app/ingredients/publish',
            [
                'id' => $obExistsIngredient->getId()
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(false, $arResponseData['status']);
    }

    /**
     * @return void
     */
    public function testIngredientAutocomplete(): void
    {

        $this->client->request(
            'POST',
            '/app/autocomplete/ingredient',
            [
                'search' => 'Сах'
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertStringContainsString('Саха', $arResponseData[0]['name']);
    }

    /**
     * @return void
     */
    public function testIngredientAutocompleteNO(): void
    {

        $this->client->request(
            'POST',
            '/app/autocomplete/ingredient',
            [
                'search' => Helpers::getRandString()
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertCount(0, $arResponseData);
    }
}
