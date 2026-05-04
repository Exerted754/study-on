<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransactionPageTest extends WebTestCase
{
    private function loginUser(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
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

        return $client;
    }

    public function testGuestCannotOpenTransactions(): void
    {
        $client = static::createClient();

        $client->request('GET', '/transactions');

        $this->assertResponseRedirects('/login');
    }

    public function testUserCanOpenTransactions(): void
    {
        $client = $this->loginUser();

        $client->request('GET', '/transactions');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'История операций');
        $this->assertSelectorTextContains('body', 'Пополнение');
        $this->assertSelectorTextContains('body', 'Оплата');
    }
}
