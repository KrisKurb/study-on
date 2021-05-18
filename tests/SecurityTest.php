<?php


namespace App\Tests;

use App\Tests\Login\Auth;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\DomCrawler\Crawler;

class SecurityTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

        // Тест авторизации пользователя
    public function testAuth(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        // Авторизируемся обычным пользователем
        $data = [
            'username' => 'user@mail.ru',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);

        // Неуспешная авторизация
        $client = self::getClient();

        // Разлогинемся
        $linkLogout = $crawler->selectLink('Выход')->link();
        $crawler = $client->click($linkLogout);
        $this->assertResponseRedirect();
        self::assertEquals('/logout', $client->getRequest()->getPathInfo());

        // Редиректит
        $crawler = $client->followRedirect();
        $this->assertResponseRedirect();
        self::assertEquals('/', $client->getRequest()->getPathInfo());
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        // Авторизуемся с обычным пользователем с неверным паролем
        $data = [
            'username' => 'user@mail.ru',
            'password' => '456456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $auth = new Auth();
        $auth->setSerializer($this->serializer);

        $requestData = json_decode($requestData, true);

        // Заполним форму
        $form = $crawler->selectButton('Войти')->form();
        $form['email'] = $requestData['username'];
        $form['password'] = $requestData['password'];
        $client->submit($form);

        // Проверим, что редиректа на курсы не было
        self::assertFalse($client->getResponse()->isRedirect('/courses/'));
        $crawler = $client->followRedirect();

        // Проверим на ошибку
        $error = $crawler->filter('#errors');
        self::assertEquals('Проверьте правильность введёного логина и пароля', $error->text());
    }

        // Тест регистрации пользователя
    public function testRegister(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $auth->getBillingClient();
        // Регистрация с валидными значениями
        $client = static::getClient();
        $crawler = $client->request('GET', '/register');

        // Проверим статуса ответа
        $this->assertResponseOk();

        // Заполним с формой
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['registration[username]'] = 'other@mail.ru';
        $form['registration[password][first]'] = '123456';
        $form['registration[password][second]'] = '123456';

        // Отправим форму
        $crawler = $client->submit($form);

        // Проверим ошибки
        $errors = $crawler->filter('span.form-error-message');
        self::assertCount(0, $errors);

        // Редирект на
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());

        // Регистрация с невалидными значениями
        // Разлогинемся
        $linkLogout = $crawler->selectLink('Выход')->link();
        $crawler = $client->click($linkLogout);
        $this->assertResponseRedirect();
        self::assertEquals('/logout', $client->getRequest()->getPathInfo());

        // Редиректит
        $crawler = $client->followRedirect();
        $this->assertResponseRedirect();
        self::assertEquals('/', $client->getRequest()->getPathInfo());
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        // Страница регистрации
        $client = static::getClient();
        $crawler = $client->request('GET', '/register');

        // Проверим статус ответа
        $this->assertResponseOk();

        // Заполненим форму пустыми значениями
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form['registration[username]'] = '';
        $form['registration[password][first]'] = '';
        $form['registration[password][second]'] = '';

        // Отправлим форму
        $crawler = $client->submit($form);

        // Ошибки
        $errors = $crawler->filter('span.form-error-message');
        self::assertCount(2, $errors);
        $errorsMessage = $errors->each(function (Crawler $node) {
            return $node->text();
        });

        // Проверим сообщения
        self::assertEquals('Поле email не может быть пустым', $errorsMessage[0]);
        self::assertEquals('Введите пароль', $errorsMessage[1]);

        // Заполненим форму с неправильным email и коротким паролем
        $form['registration[username]'] = 'other';
        $form['registration[password][first]'] = '123';
        $form['registration[password][second]'] = '123';

        // Отправлим форму
        $crawler = $client->submit($form);

        // Ошибки
        $errors = $crawler->filter('span.form-error-message');
        self::assertCount(2, $errors);
        $errorsMessage = $errors->each(function (Crawler $node, $i) {
            return $node->text();
        });

        // Проверим сообщения
        self::assertEquals('Неверно указана почта', $errorsMessage[0]);
        self::assertEquals('Ваш пароль менее, чем 6 символов', $errorsMessage[1]);

        // Заполненим форму с разными паролями
        $form['registration[username]'] = 'other@mail.ru';
        $form['registration[password][first]'] = '123456';
        $form['registration[password][second]'] = '123';

        // Отправлим форму
        $crawler = $client->submit($form);

        // Ошибки
        $errors = $crawler->filter('span.form-error-message');
        self::assertCount(1, $errors);
        $errorsValues = $errors->each(function (Crawler $node, $i) {
            return $node->text();
        });

        // Проверим сообщения
        self::assertEquals('Пароли должны совпадать', $errorsValues[0]);
    }
}