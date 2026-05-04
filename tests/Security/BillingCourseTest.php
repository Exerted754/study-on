<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BillingCourseTest extends WebTestCase
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

    public function testCoursePageShowsBillingInfo(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Стоимость курса');
        $this->assertSelectorTextContains('body', '199.99');
    }

    public function testUserCanPayCourse(): void
    {
        $client = $this->loginUser();

        $crawler = $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Купить курс')->form();

        $client->submit($form);

        $this->assertResponseRedirects('/courses/1');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Курс успешно оплачен');
    }
}
