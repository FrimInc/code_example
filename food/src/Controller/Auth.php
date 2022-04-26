<?php

/** @noinspection ALL */

namespace App\Controller;

use App\Entity\TUsers;
use App\Exceptions\ExceptionService;
use App\General\Security\LoginFormAuthenticator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class Auth extends PageController
{

    public const REGISTRATION_SECRET = 'isSecretReg';
    public const REGISTER_OK         = 'Вы зерегистрировались';

    /**
     * @param AuthenticationUtils $authenticationUtils
     * @Route("/auth")
     *
     * @return Response
     */
    public function auth(AuthenticationUtils $authenticationUtils): Response
    {
        $arResult = [
            'status'  => true,
            'message' => 'Авторизовано'
        ];

        try {
            $error = $authenticationUtils->getLastAuthenticationError();
            if ($error) {
                $arResult['status']  = false;
                $arResult['message'] = $error->getMessage();
            }
            if (!$authenticationUtils->getLastUsername()) {
                $arResult['status']  = false;
                $arResult['message'] = 'Вы не авторизованы';
            }
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException->getMessage();
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/register")
     *
     * @return Response
     */
    public function register(Request $obRequest): Response
    {
        $arResult = [
            'status'  => true,
            'message' => self::REGISTER_OK,
            'code'    => 0
        ];

        try {
            $obLoginForm = LoginFormAuthenticator::getInstance();

            /** @noinspection PhpUnhandledExceptionInspection */
            if (!$obRequest->get('recaptchaToken') || !$obLoginForm->checkCaptcha($obRequest->get('recaptchaToken'))) {
                ExceptionService::getException(ExceptionService::YOU_ROBOT);
            }

            if (self::REGISTRATION_SECRET && !$obRequest->get('secret') == self::REGISTRATION_SECRET) {
                ExceptionService::getException(ExceptionService::REGISTRATION_CLOSED);
            }

            if (!($strEmail = $obRequest->get('login'))) {
                ExceptionService::getException(ExceptionService::EMAIL_EMPTY);
            }

            if (!($strName = $obRequest->get('name'))) {
                ExceptionService::getException(ExceptionService::NAME_EMPTY);
            }

            if (!($strLastName = $obRequest->get('lastName'))) {
                ExceptionService::getException(ExceptionService::NAME_EMPTY);
            }

            if (strlen($strEmail) < 8) {
                ExceptionService::getException(ExceptionService::EMAIL_SHORT);
            }

            if ($obLoginForm->getUserByEmail($strEmail)) {
                ExceptionService::getException(ExceptionService::USER_EXISTS);
            }

            $strPassword        = $obRequest->get('password');
            $strConfirmPassword = $obRequest->get('confirmPassword');

            if (($strPassword != $strConfirmPassword) || !$strPassword || !$strConfirmPassword) {
                ExceptionService::getException(ExceptionService::PASSWORD_NOT_CONFIRMED);
            }

            if (strlen($strPassword) < 8) {
                ExceptionService::getException(ExceptionService::PASSWORD_SHORT);
            }

            $obUser = new TUsers();
            $obUser
                ->setId(0)
                ->setLogin($strEmail)
                ->setPassword($obLoginForm->encodePassword($strPassword, $obUser))
                ->setName($strName)
                ->setLastName($strLastName);

            $this->getDoctrine()->getManager()->persist($obUser);
            $this->getDoctrine()->getManager()->flush();
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException->getMessage();
            $arResult['code']    = $eException->getCode();
        }

        return $this->json($arResult);
    }
}
