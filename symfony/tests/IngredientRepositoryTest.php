<?php

namespace App\Tests\Repository;

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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IngredientRepositoryTest extends KernelTestCase
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

    /**
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function setUp(): void
    {
        $obKernel = self::bootKernel();

        self::$entityManager = $obKernel->getContainer()
            ->get('doctrine')
            ->getManager();

        self::$obIngredientRepository = self::$entityManager->getRepository(TIngredient::class);
        self::$obUnitRepository       = self::$entityManager->getRepository(TUnits::class);
        self::$obUserRepository       = self::$entityManager->getRepository(TUsers::class);
        self::$obTypeRepository       = self::$entityManager->getRepository(TIngredientType::class);

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
    }

    /**
     * @return void
     */
    public function testGetVisibleForUserOK(): void
    {
        $arResult = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser);

        $this->assertInstanceOf(TIngredient::class, $arResult[0]);
    }

    /**
     * @return void
     */
    public function testGetVisibleForUserFilterAccess(): void
    {

        $arPassedStatuses = array_fill_keys(array_keys(IngredientRepository::ACCESS_STATUS), false);

        foreach (IngredientRepository::ACCESS_STATUS as $strAccessCode => $strAccessName) {
            $arResult = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser, [
                'access' => [
                    $strAccessCode
                ]
            ]);

            $arTmpStatuses = array_fill_keys(array_keys(IngredientRepository::ACCESS_STATUS), false);

            foreach ($arResult as $obIngredient) {
                $arTmpStatuses[$obIngredient->getAccess()] = true;
            }
            $arPassedStatuses[$strAccessCode] = count(array_filter($arTmpStatuses)) <= 1;
        }

        $this->assertNotContains(false, $arPassedStatuses, print_r($arPassedStatuses, true));
    }

    /**
     * @return void
     */
    public function testGetVisibleForUserFilterType(): void
    {
        $arResult = self::$obIngredientRepository->getVisibleForUser(
            self::$obRegularUser,
            ['type' => Constants::DEFAULT_TYPE]
        );

        $arFoundTypes = [];

        foreach ($arResult as $obIngredient) {
            $arFoundTypes[$obIngredient->getType()->getId()] = true;
        }

        $this->assertSame(1, count($arFoundTypes));
    }

    /**
     * @return void
     */
    public function testGetVisibleForUserFilterTypeAccess(): void
    {

        $arResult = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser);

        $obTestIngredient = $arResult[0];

        $arResult = self::$obIngredientRepository->getVisibleForUser(
            self::$obRegularUser,
            [
                'type'   => $obTestIngredient->getType(),
                'access' => $obTestIngredient->getAccess()
            ]
        );

        $arFoundTypes  = [];
        $arFoundAccess = [];

        foreach ($arResult as $obIngredient) {
            $arFoundTypes[$obIngredient->getType()->getId()] = true;
            $arFoundAccess[$obIngredient->getAccess()]       = true;
        }

        $this->assertSame(1, count($arFoundTypes));
        $this->assertSame(1, count($arFoundAccess));
    }

    /**
     * @return void
     */
    public function testGetVisibleForUserFilterTypeEmpty(): void
    {
        $arResult = self::$obIngredientRepository->getVisibleForUser(
            self::$obRegularUser,
            ['type' => 99999999]
        );

        $this->assertSame(0, count($arResult));
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutExistsNotFound(): void
    {
        $this->expectExceptionCode(ExceptionFactory::NOT_FOUND['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'id' => -1
        ]), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutExistsNoAccess(): void
    {
        $arResult = self::$obIngredientRepository->getVisibleForUser(self::$obRegularUser);

        $obTestIngredient = $arResult[0];

        $this->expectExceptionCode(ExceptionFactory::NO_ACCESS_EDIT['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'id' => $obTestIngredient->getId()
        ]), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutExistsNameUpdate(): void
    {
        $arResult = self::$obIngredientRepository->getVisibleForUser(self::$obAdminUser);

        $obTestIngredient = $arResult[0];

        $this->expectExceptionCode(ExceptionFactory::INGREDIENT_NAME_EXISTS['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'id'   => $obTestIngredient->getId(),
            'name' => $arResult[1]->getName()
        ]), self::$obAdminUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutExistsName(): void
    {
        $this->expectExceptionCode(ExceptionFactory::INGREDIENT_NAME_EXISTS['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'name' => '??????????'
        ]), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutBadUnits(): void
    {
        $this->expectExceptionCode(ExceptionFactory::NOT_FOUND['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'name'  => Helpers::getRandString(),
            'units' => 9999
        ]), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutBadType(): void
    {
        $this->expectExceptionCode(ExceptionFactory::NOT_FOUND['code']);
        self::$obIngredientRepository->put(array_merge(self::$arValidIngredient, [
            'name' => Helpers::getRandString(),
            'type' => 9999
        ]), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutOk(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutOkDefaultUnitType(): void
    {
        unset(self::$arValidIngredient['units']);
        unset(self::$arValidIngredient['type']);
        unset(self::$arValidIngredient['minimum']);

        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);

        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutOkUpdateAccessOk(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        self::$arValidIngredient['id'] = $obNewIngredient->getId();

        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);

        $this->assertSame(self::$arValidIngredient['id'], $obNewIngredient->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutOkUpdateAccessAdmin(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        self::$arValidIngredient['id'] = $obNewIngredient->getId();

        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obAdminUser);

        $this->assertSame(self::$arValidIngredient['id'], $obNewIngredient->getId());
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testPutErrorUpdateAccessAdmin(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obAdminUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        self::$arValidIngredient['id'] = $obNewIngredient->getId();
        $this->expectExceptionCode(ExceptionFactory::NO_ACCESS_EDIT['code']);
        self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     * @throws \Exception
     */
    public function testPutOkDeleteOk(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        $boolRes = self::$obIngredientRepository->delete($obNewIngredient->getId(), self::$obRegularUser);
        $this->assertSame(true, $boolRes);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     * @throws \Exception
     */
    public function testPutOkDeleteOkAdmin(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        $boolRes = self::$obIngredientRepository->delete($obNewIngredient->getId(), self::$obAdminUser);
        $this->assertSame(true, $boolRes);
    }

    /**
     * @return void
     * @throws \App\Exceptions\FieldValidateException
     * @throws \Exception
     */
    public function testPutOkDeleteError(): void
    {
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obAdminUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        $this->expectExceptionCode(ExceptionFactory::NO_ACCESS_EDIT['code']);
        self::$obIngredientRepository->delete($obNewIngredient->getId(), self::$obRegularUser);
    }

    /**
     * @return void
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \App\Exceptions\FieldValidateException
     */
    public function testFindByName(): void
    {
        // ?????? ????????
        $arResult = self::$obIngredientRepository->findByName('??????????', self::$obRegularUser);
        $this->assertInstanceOf(TIngredient::class, $arResult[0]);

        // ???? ????????????????????
        $arResult = self::$obIngredientRepository->findByName('????????????', self::$obRegularUser);
        $this->assertSame([], $arResult);

        // ???????? ????????
        $obNewIngredient = self::$obIngredientRepository->put(self::$arValidIngredient, self::$obAdminUser);
        $this->assertInstanceOf(TIngredient::class, $obNewIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obNewIngredient->getName());

        $arResult = self::$obIngredientRepository->findByName(
            self::$arValidIngredient['name'],
            self::$obAdminUser
        );
        $this->assertInstanceOf(TIngredient::class, $arResult[0]);

        //????????????????????????????????
        $arResult  = self::$obIngredientRepository->findMyUnmoderated(
            self::$obAdminUser
        );
        $boolFound = false;
        foreach ($arResult as $obIngredient) {
            if ($boolFound = (self::$arValidIngredient['name'] == $obIngredient->getName())) {
                break;
            }
        }
        $this->assertSame(true, $boolFound);

        //?????????????? ???? ??????
        $obIngredient = self::$obIngredientRepository->checkByName('??????????');
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame('??????????', $obIngredient->getName());

        //?????????????? ??????
        $obIngredient = self::$obIngredientRepository->checkByName(self::$arValidIngredient['name']);
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obIngredient->getName());

        //?????????????? ??????
        $obIngredient = self::$obIngredientRepository->findOneByName(
            self::$arValidIngredient['name'],
            self::$obAdminUser
        );
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame(self::$arValidIngredient['name'], $obIngredient->getName());

        //?????????????? ??????
        $obIngredient = self::$obIngredientRepository->findOneByName(
            self::$arValidIngredient['name'],
            self::$obRegularUser
        );
        $this->assertSame(null, $obIngredient);

        //???? ID ??????
        $obIngredient = self::$obIngredientRepository->getVisibleByID(
            $obNewIngredient->getId(),
            self::$obRegularUser
        );
        $this->assertSame(null, $obIngredient);

        //???? ID ????
        $obIngredient = self::$obIngredientRepository->getVisibleByID(
            $obNewIngredient->getId(),
            self::$obAdminUser
        );
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame($obNewIngredient->getId(), $obIngredient->getId());

        //?????????? ?????? ????????????
        $obIngredient = self::$obIngredientRepository->getVisibleByID(
            self::$obIngredientRepository->checkByName('??????????')->getId(),
            self::$obAdminUser
        );
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame(self::$obIngredientRepository->checkByName('??????????')->getId(), $obIngredient->getId());

        //?????????? ?????? ????????
        $obIngredient = self::$obIngredientRepository->getVisibleByID(
            self::$obIngredientRepository->checkByName('??????????')->getId(),
            self::$obAdminUser
        );
        $this->assertInstanceOf(TIngredient::class, $obIngredient);
        $this->assertSame(self::$obIngredientRepository->checkByName('??????????')->getId(), $obIngredient->getId());


        // ???????? ??????????
        $arResult = self::$obIngredientRepository->findByName(
            self::$arValidIngredient['name'],
            self::$obRegularUser
        );
        $this->assertSame([], $arResult);
    }
}
