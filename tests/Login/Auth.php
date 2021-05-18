<?php


namespace App\Tests\Login;

use App\Model\UserDto;
use App\Service\BillingClient;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\Client;
use JMS\Serializer\SerializerInterface;

class Auth extends AbstractTest
{
    private $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function auth(string $data)
    {
        /** @var UserDto $userDto */
        $userDto = $this->serializer->deserialize($data, UserDto::class, 'json');
        $this->getBillingClient();
        $client = self::getClient();

        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = $userDto->getUsername();
        $form['password'] = $userDto->getPassword();
        $client->submit($form);

        $error = $crawler->filter('#errors');
        self::assertCount(0, $error);

        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        return $crawler;
    }


    public function getBillingClient(): void
    {

        self::getClient()->disableReboot();

        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock($this->serializer)
        );
    }
}