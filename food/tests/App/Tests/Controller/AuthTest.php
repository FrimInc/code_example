<?php

namespace App\Tests\Controller;

use App\Controller\Auth;
use App\Exceptions\ExceptionService;
use App\General\Security\LoginFormAuthenticator;
use App\Tests\Helpers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{

    /**
     * @return void
     */
    public function testCaptchaN(): void
    {
        $obClient = static::createClient();

        $obClient->request('POST', '/register', [
            'recaptchaToken' => 1
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        if (Auth::REGISTRATION_SECRET) {
            $arResponseData = json_decode($obResponse->getContent(), true);
            $this->assertSame(
                ExceptionService::YOU_ROBOT['code'],
                $arResponseData['code']
            );
        }
    }

    /**
     * @return void
     */
    public function testCaptchaY(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = Helpers::getRandString();

        $obClient->request('POST', '/register', [
            'recaptchaToken' => LoginFormAuthenticator::$strToken
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());

        $arResponseData = json_decode($obResponse->getContent(), true);
        $this->assertNotSame(
            ExceptionService::YOU_ROBOT['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationClosed(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = Helpers::getRandString();

        $obClient->request('POST', '/register', [
            'recaptchaToken' => LoginFormAuthenticator::$strToken
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        if (Auth::REGISTRATION_SECRET) {
            $arResponseData = json_decode($obResponse->getContent(), true);
            $this->assertSame(
                ExceptionService::REGISTRATION_CLOSED['code'],
                $arResponseData['code']
            );
        }
    }

    /**
     * @return void
     */
    public function testRegistrationLoginEmpty(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = Helpers::getRandString();

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::EMAIL_EMPTY['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationNameEmpty(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => Helpers::getRandEmail()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::NAME_EMPTY['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationLastNameEmpty(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => Helpers::getRandEmail(),
            'name'           => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::NAME_EMPTY['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationLoginShort(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => Helpers::getRandString(3),
            'name'           => Helpers::getRandString(),
            'lastName'       => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::EMAIL_SHORT['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationUserExists(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => 'friminc@yandex.ru',
            'name'           => Helpers::getRandString(),
            'lastName'       => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::USER_EXISTS['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationPasswordEmpty(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $obClient->request('POST', '/register', [
            'secret'         => Auth::REGISTRATION_SECRET,
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => Helpers::getRandEmail(),
            'name'           => Helpers::getRandString(),
            'lastName'       => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::PASSWORD_NOT_CONFIRMED['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationPasswordLength(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $strPass = substr(md5(time()), 0, 5);

        $obClient->request('POST', '/register', [
            'secret'          => Auth::REGISTRATION_SECRET,
            'recaptchaToken'  => LoginFormAuthenticator::$strToken,
            'login'           => Helpers::getRandEmail(),
            'password'        => $strPass,
            'confirmPassword' => $strPass,
            'name'            => Helpers::getRandString(),
            'lastName'        => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::PASSWORD_SHORT['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationEmailInvalid(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $strPass = substr(md5(time()), 0, 12);

        $obClient->request('POST', '/register', [
            'secret'          => Auth::REGISTRATION_SECRET,
            'recaptchaToken'  => LoginFormAuthenticator::$strToken,
            'login'           => Helpers::getRandString(),
            'password'        => $strPass,
            'confirmPassword' => $strPass,
            'name'            => Helpers::getRandString(),
            'lastName'        => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            ExceptionService::EMAIL_INVALID['code'],
            $arResponseData['code']
        );
    }

    /**
     * @return void
     */
    public function testRegistrationEmailIsOk(): void
    {
        $obClient = static::createClient();

        LoginFormAuthenticator::$strToken = md5(time() . microtime());

        $strPass = substr(md5(time()), 0, 12);

        $obClient->request('POST', '/register', $arRequestData = [
            'secret'          => Auth::REGISTRATION_SECRET,
            'recaptchaToken'  => LoginFormAuthenticator::$strToken,
            'login'           => Helpers::getRandEmail(),
            'password'        => $strPass,
            'confirmPassword' => $strPass,
            'name'            => Helpers::getRandString(),
            'lastName'        => Helpers::getRandString()
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            0,
            $arResponseData['code']
        );

        $this->assertSame(
            true,
            $arResponseData['status']
        );

        //ERROR
        $obClient->request('POST', '/auth', [
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => $arRequestData['login'] . '11',
            'password'       => $strPass,
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            false,
            $arResponseData['status']
        );

        // OK
        $obClient->request('POST', '/auth', [
            'recaptchaToken' => LoginFormAuthenticator::$strToken,
            'login'          => $arRequestData['login'],
            'password'       => $strPass,
        ]);

        $this->assertResponseIsSuccessful();
        $obResponse = $obClient->getResponse();
        $this->assertSame(200, $obResponse->getStatusCode());
        $arResponseData = json_decode($obResponse->getContent(), true);

        $this->assertSame(
            true,
            $arResponseData['status']
        );
    }
}
