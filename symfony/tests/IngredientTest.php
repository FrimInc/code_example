<?php

namespace App\Tests\Controller;

use App\Constants;
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

    private static ?TIngredientType $obType;
    private static ?TIngredientType $obType2;
    private static ?TUnits          $obUnit;

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

        static::$obType          = self::$obTypeRepository->findAll()[0];
        static::$obType2         = self::$obTypeRepository->findAll()[1];
        static::$obUnit          = self::$obUnitRepository->findAll()[0];
        self::$arValidIngredient = [
            'name'    => Helpers::getRandString(),
            'units'   => static::$obUnit->getId(),
            'type'    => static::$obType->getId(),
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
        $this->client->request('POST', '/app/ingredients');
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode(), $obResponse->getContent());
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
        $this->assertArrayHasKey('item', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);
    }

    /**
     * @return void
     * @depends testIngredientPut
     */
    public function testIngredientDelete(): void
    {
        $this->client->request('POST', '/app/ingredients/add', self::$arValidIngredient);

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $strContent = $obResponse->getContent();
        $this->assertJson($strContent);
        $arResponseData = json_decode($strContent, true);

        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertArrayHasKey('item', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);

        $intTestItemID = $arResponseData['item']['id'];

        $this->client->request('POST', '/app/ingredients/delete', [
            'id' => $intTestItemID
        ]);

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $strContent = $obResponse->getContent();
        $this->assertJson($strContent);
        $arResponseData = json_decode($strContent, true);

        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);

        $this->client->request('POST', '/app/ingredients/delete', [
            'id' => $intTestItemID
        ]);

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $strContent = $obResponse->getContent();
        $this->assertJson($strContent);
        $arResponseData = json_decode($strContent, true);

        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(false, $arResponseData['status']);
        $this->assertSame(ExceptionFactory::NO_ACCESS_EDIT['code'], $arResponseData['code']);
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
    public function testIngredientPrivate(): void
    {
        $this->client->request('POST', '/app/ingredients/add', self::$arValidIngredient);
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertArrayHasKey('item', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);

        $intTestId = $arResponseData['item']['id'];

        $this->client->request(
            'POST',
            '/app/ingredients',
            [
                'access' => 'P'
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $boolFoundTestId = [];
        $arFoundAccess   = [];

        foreach ($arResponseData as $arIngredient) {
            $boolFoundTestId                        = $boolFoundTestId || $arIngredient['id'] == $intTestId;
            $arFoundAccess[$arIngredient['access']] = true;
        }

        $this->assertSame(true, $boolFoundTestId);
        $this->assertSame(1, count($arFoundAccess), print_r($arFoundAccess, true));
    }

    /**
     * @return void
     * @depends testIngredientPrivate
     */
    public function testIngredientPublish(): void
    {

        $this->client->request('POST', '/app/ingredients/add', self::$arValidIngredient);
        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertArrayHasKey('item', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);

        $intTestId = $arResponseData['item']['id'];

        $this->client->request(
            'POST',
            '/app/ingredients/publish',
            [
                'id' => $intTestId
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertArrayHasKey('status', $arResponseData);
        $this->assertSame(true, $arResponseData['status']);

        $this->client->request(
            'POST',
            '/app/ingredients',
            [
                'access' => 'M'
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $boolFoundTestId = [];
        $arFoundAccess   = [];

        foreach ($arResponseData as $arIngredient) {
            $boolFoundTestId                        = $boolFoundTestId || $arIngredient['id'] == $intTestId;
            $arFoundAccess[$arIngredient['access']] = true;
        }

        $this->assertSame(true, $boolFoundTestId);
        $this->assertSame(1, count($arFoundAccess), print_r($arFoundAccess, true));
    }

    /**
     * @return void
     */
    public function testIngredientAllIngredients(): void
    {

        $this->client->request(
            'POST',
            '/app/ingredients',
            [
                'access' => 'O'
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $arFoundAccess = [];

        foreach ($arResponseData as $arIngredient) {
            $arFoundAccess[$arIngredient['access']] = true;
        }

        $this->assertSame(1, count($arFoundAccess), print_r($arFoundAccess, true));
    }

    /**
     * @return void
     */
    public function testIngredientType(): void
    {

        $this->client->request(
            'POST',
            '/app/ingredients',
            [
                'type' => Constants::DEFAULT_TYPE
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $arFoundType = [];

        foreach ($arResponseData as $arIngredient) {
            $arFoundType[$arIngredient['type']['id']] = true;
        }

        $this->assertSame(1, count($arFoundType), print_r($arFoundType, true));
    }

    /**
     * @return void
     */
    public function testIngredientTypeTwoo(): void
    {

        $arCheckTypes = array_unique(
            [
                static::$obType->getId(),
                static::$obType2->getId()
            ]
        );

        $this->client->request(
            'POST',
            '/app/ingredients',
            [
                'type' => $arCheckTypes
            ]
        );

        $obResponse = $this->client->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $this->assertJson($obResponse->getContent());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $arFoundType = [];

        foreach ($arResponseData as $arIngredient) {
            $arFoundType[$arIngredient['type']['id']] = true;
        }

        $this->assertSame(2, count($arFoundType), print_r($arFoundType, true));
        $this->assertContains(static::$obType->getId(), array_keys($arFoundType));
        $this->assertContains(static::$obType2->getId(), array_keys($arFoundType));
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
