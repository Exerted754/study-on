<?php

namespace App\Tests\Security;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccessTest extends WebTestCase
{
    private function login(string $email, string $password): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            'email' => $email,
            'password' => $password,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');

        return $client;
    }

    public function testGuestCanOpenCoursesList(): void
    {
        $client = static::createClient();

        $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');
    }

    public function testGuestCanOpenCoursePage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();

        $link = $crawler->filter('.list-group-item h5 a')->first()->link();

        $client->click($link);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Уроки');
    }

    public function testGuestCannotOpenLessonPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/lessons/1');

        $this->assertResponseRedirects('/login');
    }

    public function testUserCanOpenLessonPage(): void
    {
        $client = $this->login('user@test.local', 'Topparol');

        $client->request('GET', '/lessons/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Содержимое урока');
    }

    public function testUserCannotOpenCourseCreatePage(): void
    {
        $client = $this->login('user@test.local', 'Topparol');

        $client->request('GET', '/courses/new');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanOpenCourseCreatePage(): void
    {
        $client = $this->login('admin@test.local', 'Admin_pass');

        $client->request('GET', '/courses/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Создать новый курс');
    }

    public function testUserDoesNotSeeAdminButtons(): void
    {
        $client = $this->login('user@test.local', 'Topparol');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('a[href="/courses/new"]');
        $this->assertSelectorTextNotContains('body', 'Редактировать');
    }

    public function testAdminSeesAdminButtons(): void
    {
        $client = $this->login('admin@test.local', 'Admin_pass');

        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Добавить курс');
        $this->assertSelectorTextContains('body', 'Редактировать');
    }
}
