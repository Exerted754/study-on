<?php

namespace App\Tests\Controller;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    private function loginAdmin(KernelBrowser $client): void
    {
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
    }

    public function testIndexPageWorks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Курсы');

        $this->assertCount(3, $crawler->filter('.list-group-item'));
    }

    public function testShowCourse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/1');

        $this->assertResponseIsSuccessful();
    }

    public function test404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/courses/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditCourseWorks(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $client->request('GET', '/courses/1/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateCourse(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'test-course',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'тестовое описание',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'Тестовый курс');
    }

    public function testEditCourseSubmit(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/courses/1/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'updated-course',
            'course[title]' => 'Обновленный курс',
            'course[description]' => 'Обновленное описание',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'Обновленный курс');
    }

    public function testCreateCourseValidation(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/courses/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => '',
            'course[title]' => '',
            'course[description]' => '',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Введите код курса.');
        $this->assertSelectorTextContains('body', 'Введите название курса.');
    }

    public function testEditCourseValidation(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/courses/1/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => '',
            'course[title]' => '',
            'course[description]' => 'Описание',
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('body', 'Введите код курса.');
        $this->assertSelectorTextContains('body', 'Введите название курса.');
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();
        $this->loginAdmin($client);

        $crawler = $client->request('GET', '/courses/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'delete-course',
            'course[title]' => 'Курс на удаление',
            'course[description]' => 'временный курс',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses');
        $crawler = $client->followRedirect();

        $courseLinks = $crawler->filter('.list-group-item h5 a');
        $countBefore = $courseLinks->count();

        $link = $crawler->selectLink('Курс на удаление')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $deleteForm = $crawler->selectButton('Удалить курс')->form();
        $client->submit($deleteForm);

        $this->assertResponseRedirects('/courses');
        $crawler = $client->followRedirect();

        $courseLinksAfter = $crawler->filter('.list-group-item h5 a');
        $countAfter = $courseLinksAfter->count();

        $titlesAfter = $courseLinksAfter->each(function ($node) {
            return trim($node->text());
        });

        $this->assertSame($countBefore - 1, $countAfter);
        $this->assertNotContains('Курс на удаление', $titlesAfter);
    }
}
