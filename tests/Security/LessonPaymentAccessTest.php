<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonPaymentAccessTest extends WebTestCase
{
    private function loginUser(KernelBrowser $client, BillingClientMock $billingClientMock): void
    {
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            $billingClientMock
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            'email' => 'user@test.local',
            'password' => 'Topparol',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');
    }

    public function testUserCannotOpenPaidLessonWithoutPayment(): void
    {
        $client = static::createClient();

        $this->loginUser(
            $client,
            new BillingClientMock(['php-basic'])
        );

        $client->request('GET', '/lessons/1');

        $this->assertResponseRedirects('/courses/1');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            'body',
            'Для просмотра урока необходимо оплатить курс'
        );
    }

    public function testUserCanOpenPaidLessonAfterPayment(): void
    {
        $client = static::createClient();

        $this->loginUser(
            $client,
            new BillingClientMock()
        );

        $client->request('GET', '/lessons/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Содержимое урока');
    }
}
