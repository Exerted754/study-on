<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MainControllerTest extends WebTestCase
{
    public function testHomeRedirectsToCourses(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/courses');
    }
}
