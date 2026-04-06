<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursesData = [
            [
                'code' => 'php-basic',
                'title' => 'Основы PHP',
                'description' => 'Базовый курс по PHP: синтаксис, переменные, условия, циклы и функции.',
                'lessons' => [
                    [
                        'title' => 'Переменные и типы данных',
                        'content' => 'В этом уроке рассматриваются базовые типы данных, переменные и операции.',
                        'number' => 1,
                    ],
                    [
                        'title' => 'Условия и циклы',
                        'content' => 'Разбираем if, else, switch, while, for и foreach.',
                        'number' => 2,
                    ],
                    [
                        'title' => 'Функции',
                        'content' => 'Учимся создавать собственные функции и передавать параметры.',
                        'number' => 3,
                    ],
                ],
            ],
            [
                'code' => 'symfony-start',
                'title' => 'Старт с Symfony',
                'description' => 'Знакомство со структурой проекта Symfony, маршрутами и контроллерами.',
                'lessons' => [
                    [
                        'title' => 'Структура проекта',
                        'content' => 'Разбираем папки src, config, templates, public и их назначение.',
                        'number' => 1,
                    ],
                    [
                        'title' => 'Маршруты и контроллеры',
                        'content' => 'Создаём первые роуты и экшены контроллеров.',
                        'number' => 2,
                    ],
                    [
                        'title' => 'Twig шаблоны',
                        'content' => 'Подключаем шаблоны и передаём в них данные из контроллера.',
                        'number' => 3,
                    ],
                ],
            ],
            [
                'code' => 'postgresql-base',
                'title' => 'PostgreSQL для веб-разработки',
                'description' => 'Введение в PostgreSQL, таблицы, связи и базовые SQL-запросы.',
                'lessons' => [
                    [
                        'title' => 'Создание таблиц',
                        'content' => 'Учимся создавать таблицы и выбирать правильные типы полей.',
                        'number' => 1,
                    ],
                    [
                        'title' => 'Связи между таблицами',
                        'content' => 'Разбираем внешние ключи и связи между сущностями.',
                        'number' => 2,
                    ],
                    [
                        'title' => 'Базовые SQL-запросы',
                        'content' => 'Пишем select, insert, update и delete на простых примерах.',
                        'number' => 3,
                    ],
                ],
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setCode($courseData['code']);
            $course->setTitle($courseData['title']);
            $course->setDescription($courseData['description']);

            foreach ($courseData['lessons'] as $lessonData) {
                $lesson = new Lesson();
                $lesson->setCourse($course);
                $lesson->setTitle($lessonData['title']);
                $lesson->setContent($lessonData['content']);
                $lesson->setNumber($lessonData['number']);

                $manager->persist($lesson);
            }

            $manager->persist($course);
        }

        $manager->flush();
    }
}
