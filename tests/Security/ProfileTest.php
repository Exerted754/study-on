<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileTest extends WebTestCase
{
    public function testGuestCannotOpenProfile(): void
    {
        $client = static::createClient();

        $client->request('GET', '/profile');

        $this->assertResponseRedirects('/login');
    }

    public function testUserCanOpenProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            'email' => 'user@test.local',
            'password' => 'Topparol',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'user@test.local');
        $this->assertSelectorTextContains('body', 'Пользователь');
        $this->assertSelectorTextContains('body', '1000');
    }

    public function testAdminCanOpenProfile(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            'email' => 'admin@test.local',
            'password' => 'Admin_pass',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'admin@test.local');
        $this->assertSelectorTextContains('body', 'Администратор');
        $this->assertSelectorTextContains('body', '1000');
    }
}
