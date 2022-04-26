<?php

/** @noinspection PhpDocMissingThrowsInspection */

namespace App\General\Security;

use App\Constants;
use App\Entity\TUsers;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_auth_auth';

    private EntityManagerInterface       $entityManager;
    private UrlGeneratorInterface        $urlGenerator;
    private CsrfTokenManagerInterface    $csrfTokenManager;
    private UserPasswordEncoderInterface $passwordEncoder;
    private HttpClientInterface          $client;
    private LoggerInterface              $logger;

    /**
     * @var \App\General\Security\LoginFormAuthenticator|null
     */
    private static ?LoginFormAuthenticator $instance = null;

    /**
     * @return \App\General\Security\LoginFormAuthenticator|null
     */
    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }
        return null;
    }

    /**
     * LoginFormAuthenticator constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param UrlGeneratorInterface        $urlGenerator
     * @param CsrfTokenManagerInterface    $csrfTokenManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param HttpClientInterface          $client
     * @param LoggerInterface              $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        HttpClientInterface $client,
        LoggerInterface $logger
    )
    {
        $this->entityManager    = $entityManager;
        $this->urlGenerator     = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder  = $passwordEncoder;
        $this->client           = $client;
        $this->logger           = $logger;

        self::$instance = $this;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request)
    {

        $arCredentials = [
            'login'    => $request->get('login'),
            'password' => $request->get('password'),
            'captcha'  => $request->get('recaptchaToken')
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $arCredentials['login']
        );

        return $arCredentials;
    }

    /**
     * @param string        $plainPassword
     * @param UserInterface $userInterface
     * @return string
     */
    public function encodePassword(string $plainPassword, UserInterface $userInterface): string
    {
        return $this->passwordEncoder->encodePassword($userInterface, $plainPassword);
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @return object|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {

        $arUser = $this->getUserByEmail($credentials['login']);

        if (!$arUser) {
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        return $arUser;
    }

    /**
     * @param string $strEmail
     * @return object|null
     */
    public function getUserByEmail(string $strEmail)
    {
        return $this->entityManager->getRepository(TUsers::class)->findOneBy(['login' => $strEmail]);
    }

    public static string $strToken = '';

    /**
     * @param string $token
     * @return false|mixed
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function checkCaptcha(string $token)
    {

        if (self::$strToken && self::$strToken === $token || $_SERVER['APP_ENV'] == 'dev') {
            return true;
        }

        try {
            $arResponse = $this->client->request(
                'POST',
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'query' => [
                        'secret'   => Constants::GOOGLE_CAPTCHA,
                        'response' => $token
                    ]
                ]
            )->toArray();

            return $arResponse['success'];
        } catch (Exception $obException) {
        }
        return false;
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     * @return bool
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            return $this->checkCaptcha($credentials['captcha']) &&
                $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
        } catch (Exception $eException) {
            return false;
        }
    }

    /**
     * @param $credentials
     * Used to upgrade (rehash) the user's password automatically over time.
     * @return string
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     * @return JsonResponse
     * @throws \Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $arResult = [
            'status'  => true,
            'message' => 'Вы авторизованы'
        ];

        return new JsonResponse($arResult);
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $arResult = [
            'status'  => false,
            'message' => 'Не удалось авторизоваться'
        ];

        $this->logger->error('Ошибка авторизации');

        return new JsonResponse($arResult);
    }

    /**
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
