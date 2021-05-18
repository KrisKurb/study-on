<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\Login\Auth;
use JMS\Serializer\SerializerInterface;

class LessonTest extends AbstractTest
{
    // Стартовая страница курсов
    private $PageCourse = '/courses';
    // Стартовая страница уроков
    private $PageLesson = '/lesson';

    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }


    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    // Вызов стартовой страницы курсов
    public function getPageCourse(): string
    {
        return $this->PageCourse;
    }

    // Вызов старовой страницы уроков
    public function getPageLesson(): string
    {
        return $this->PageLesson;
    }

    /**
     * @dataProvider urlProvider
     */
        // Проверяем корректно ли отображаются страницы уроков
    public function testPageIsSuccessful($url): void
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
        $client->request('GET', $url);
        // Проверим ответ от страницы
        self::assertResponseIsSuccessful();

        // Проверим на 404 ошибку
        $client = self::getClient();
        $url = $this->getPageLesson() . '/745';
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
    public function urlProvider(): \Generator
    {
        yield [$this->getPageLesson() . '/'];
    }

        // Тест на добавления урока
    public function testLessonNew(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        // Авторизуемся админом
        $data = [
            'username' => 'admin@mail.ru',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);

        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу
        $link = $crawler->filter('a.card-link')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();


        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполненим поля формы
        $form = $crawler->selectButton('lesson_save')->form();
        // Изменяем поля в форме
        $form['lesson[name]'] = 'Тест';
        $form['lesson[material]'] = 'Тест';
        $form['lesson[number]'] = '11';

        // Получим id курса, который создали
        $course = static::getEntityManager()->getRepository(Course::class)->
        findOneBy(['id' => $form['lesson[course]']->getValue()]);
        self::assertNotEmpty($course);
        // Отправим форму
        $client->submit($form);
        // Проверка редиректа
        self::assertTrue($client->getResponse()->isRedirect($this->getPageCourse() . '/' . $course->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Перейдем на страницу урока, который добавили
        $link = $crawler->filter('ol > li > a')->first()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Удалим урок
        $client->submitForm('lesson_delete');
        // Проверим редирект
        self::assertTrue($client->getResponse()->isRedirect($this->getPageCourse() . '/' . $course->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Проверим заполнение формы невалидными значениями
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдем к последнему курсу
        $link = $crawler->filter('a.card-link')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдем к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполнение формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => '',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '10',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Name can not be blank', $error->text());

        // Проверка перезаполнения поля name (более 255 символов)
        // Заполнение формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '10',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Name max length is 255 symbols', $error->text());

        // Невалидным поле material
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдем к последнему курсу
        $link = $crawler->filter('a.card-link')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдем к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполненим поля формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'Тест',
            'lesson[material]' => '',
            'lesson[number]' => '11',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Material field can not be empty', $error->text());

        // Тест на добавление урока с невалидным полем number
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдем к последнему курсу
        $link = $crawler->filter('a.card-link')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдем к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполненим форму
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'Тест',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Number field can not be empty', $error->text());
    }

        // Тест редактирования урока
    public function testLessonEdit(): void
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        // Авторизуемся админом
        $data = [
            'username' => 'admin@mail.ru',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);
        // Главная страница с курсами
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдем к последнему курсу
        $link = $crawler->filter('a.card-link')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдем к последнему уроку
        $link = $crawler->filter('ol > li > a')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Форма редактирования
        $link = $crawler->filter('#lesson_edit')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполнение формы
        $form = $crawler->selectButton('lesson_save')->form();
        // Получаем урок по номеру
        $lesson = self::getEntityManager()->getRepository(Lesson::class)->findOneBy([
            'number' => $form['lesson[number]']->getValue(),
            'course' => $form['lesson[course]']->getValue(),
        ]);

        // Изменим поля в форме
        $form['lesson[name]'] = 'Test';
        $form['lesson[material]'] = 'Test';

        // Отправим форму
        $client->submit($form);
        // Проверка редиректа
        self::assertTrue($client->getResponse()->isRedirect($this->getPageLesson() . '/' . $lesson->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
    }
}
