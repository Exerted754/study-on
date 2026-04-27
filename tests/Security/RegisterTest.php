<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterTest extends WebTestCase
{
    public function testUserCanRegister(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'register[email]' => 'new@test.local',
            'register[password]' => 'Topparol',
            'register[passwordRepeat]' => 'Topparol',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
    }

    public function testRegisterWithShortPassword(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'register[email]' => 'short@test.local',
            'register[password]' => '123',
            'register[passwordRepeat]' => '123',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains(
            'body',
            'Пароль не должен содержать менее 6 символов'
        );
    }

    public function testRegisterWithDifferentPasswords(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'register[email]' => 'different@test.local',
            'register[password]' => 'Topparol',
            'register[passwordRepeat]' => 'Otherpass',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Пароли не совпадают');
    }

    public function testRegisterExistingEmail(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'register[email]' => 'exists@test.local',
            'register[password]' => 'Topparol',
            'register[passwordRepeat]' => 'Topparol',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains(
            'body',
            'Пользователь с таким email уже существует'
        );
    }
}
