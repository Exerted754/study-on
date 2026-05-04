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

    public function testUserCanFilterTransactionsByType(): void
    {
        $client = $this->loginUser();

        $client->request('GET', '/transactions?type=deposit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('tbody', 'Пополнение');
        $this->assertSelectorTextNotContains('tbody', 'php-basic');
        $this->assertSelectorTextNotContains('tbody', 'symfony-start');
    }

    public function testUserCanFilterTransactionsByCourseCode(): void
    {
        $client = $this->loginUser();

        $client->request('GET', '/transactions?course_code=php-basic');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'php-basic');
        $this->assertSelectorTextNotContains('body', 'symfony-start');
    }

    public function testUserCanFilterExpiredRentTransactions(): void
    {
        $client = $this->loginUser();

        $client->request('GET', '/transactions?skip_expired=1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'История операций');
    }

}
