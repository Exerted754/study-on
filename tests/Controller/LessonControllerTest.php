<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LessonControllerTest extends WebTestCase
{
    public function testIndexPageWorks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Уроки');
        $this->assertGreaterThan(0, $crawler->filter('.list-group-item')->count());
    }

    public function testShowLesson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/1');

        $this->assertResponseIsSuccessful();
    }

    public function test404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditLessonWorks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/1/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/new?course_id=1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Тестовый урок',
            'lesson[content]' => 'Тестовое содержимое урока',
            'lesson[number]' => 99,
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses/1');
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'Тестовый урок');
    }

    public function testCreateLessonValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/new?course_id=1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => '',
            'lesson[content]' => '',
            'lesson[number]' => '',
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Введите название урока.');
        $this->assertSelectorTextContains('body', 'Введите содержимое урока.');
        $this->assertSelectorTextContains('body', 'Укажите номер урока.');
    }

    public function testCreateLessonInvalidNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/new?course_id=1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Плохой урок',
            'lesson[content]' => 'контент',
            'lesson[number]' => 10001,
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Номер урока должен быть от 1 до 10000.');
    }

    public function testCreateLessonInvalidCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/new?course_id=1');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Урок',
            'lesson[content]' => 'контент',
            'lesson[number]' => 5,
            'lesson[course]' => 999999,
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Выбран неверный курс.');
    }

    public function testEditLessonSubmit(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/1/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Обновленный урок',
            'lesson[content]' => 'Обновленное содержимое урока',
            'lesson[number]' => 77,
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses/1');
        $client->followRedirect();

        $this->assertSelectorTextContains('body', 'Обновленный урок');
    }

    public function testEditLessonValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/1/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => '',
            'lesson[content]' => '',
            'lesson[number]' => '',
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Введите название урока.');
        $this->assertSelectorTextContains('body', 'Введите содержимое урока.');
        $this->assertSelectorTextContains('body', 'Укажите номер урока.');
    }

    public function testDeleteLesson(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/lessons/new?course_id=1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Урок на удаление',
            'lesson[content]' => 'временный урок',
            'lesson[number]' => 88,
            'lesson[course]' => 1,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/courses/1');
        $client->followRedirect();

        $crawler = $client->request('GET', '/lessons');
        $this->assertResponseIsSuccessful();

        $lessonLinks = $crawler->filter('.list-group-item h5 a');
        $countBefore = $lessonLinks->count();

        $link = $crawler->selectLink('Урок на удаление')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $deleteForm = $crawler->selectButton('Удалить урок')->form();
        $client->submit($deleteForm);

        $this->assertResponseRedirects('/courses/1');
        $client->followRedirect();

        $crawler = $client->request('GET', '/lessons');
        $lessonLinksAfter = $crawler->filter('.list-group-item h5 a');
        $countAfter = $lessonLinksAfter->count();

        $titlesAfter = $lessonLinksAfter->each(function ($node) {
            return trim($node->text());
        });

        $this->assertSame($countBefore - 1, $countAfter);
        $this->assertNotContains('Урок на удаление', $titlesAfter);
    }
}
