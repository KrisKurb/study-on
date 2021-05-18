<?php

namespace App\Tests;

use App\Tests\Login\Auth;
use JMS\Serializer\SerializerInterface;

class ProfileTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

        // Тест профиля пользователя
    public function testProfileUser(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        // Авторизуемся обычным пользователем
        $data = [
            'username' => 'user@mail.ru',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);

        $client = self::getClient();
        $client->request('GET', '/profile/');
        self::assertResponseIsSuccessful();
    }

        // Тест транзакций пользователя
    public function testUserTransactions(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        // Авторизуемся обычным пользователем
        $data = [
            'username' => 'user@mail.ru',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $auth->auth($requestData);

        $client = self::getClient();
        $crawler = $client->request('GET', '/profile/transactions');
        self::assertResponseIsSuccessful();
    }
}