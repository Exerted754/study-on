<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    public function testUserCanLogin(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'user@test.local',
            'password' => 'Topparol',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
    }

    public function testUserCannotLoginWithBadPassword(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'user@test.local',
            'password' => 'wrong',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Неверный логин или пароль');
    }

    public function testAdminCanLogin(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sign in')->form([
            'email' => 'admin@test.local',
            'password' => 'Admin_pass',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Добавить курс');
    }
}
