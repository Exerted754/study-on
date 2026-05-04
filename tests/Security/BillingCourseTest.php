<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BillingCourseTest extends WebTestCase
{
    private function loginUser(BillingClientMock $billingClientMock): KernelBrowser
    {
        $client = static::createClient();
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

        return $client;
    }

    public function testCoursePageShowsBillingInfo(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock(['php-basic'])
        );

        $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Стоимость курса');
        $this->assertSelectorTextContains('body', '199.99');
        $this->assertSelectorTextContains('body', 'Войдите, чтобы купить курс');
    }

    public function testUserCanPayCourse(): void
    {
        $client = $this->loginUser(new BillingClientMock(['php-basic']));

        $crawler = $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Купить курс')->form();

        $client->submit($form);

        $this->assertResponseRedirects('/courses/1');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Курс успешно оплачен');
    }

    public function testPaidCourseShowsAlreadyBoughtMessage(): void
    {
        $client = $this->loginUser(new BillingClientMock());

        $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Курс уже куплен');
        $this->assertSelectorNotExists('form[action="/courses/1/pay"]');
    }

    public function testCoursesListShowsBillingInfo(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('body', 'Покупка');
        $this->assertSelectorTextContains('body', '199.99');

        $this->assertSelectorTextContains('body', 'Аренда');
        $this->assertSelectorTextContains('body', '99.99');

        $this->assertSelectorTextContains('body', 'Бесплатно');
    }

    public function testCoursePageShowsUserBalance(): void
    {
        $client = $this->loginUser(new BillingClientMock(['php-basic']));

        $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Ваш баланс');
        $this->assertSelectorTextContains('body', '1000');
    }
}
