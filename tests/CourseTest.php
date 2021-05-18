<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Tests\Login\Auth;
use JMS\Serializer\SerializerInterface;

class CourseTest extends AbstractTest
{

    private $base_route = '/courses';

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

    // Метод вызова стартовой страницы курсов
    public function getPath(): string
    {
        return $this->base_route;
    }

    // Проверка на корректный http-статус для всех GET/POST методов
    /**
     * @dataProvider urlProvider
     * @param $url
     */
    public function testPageIsSuccessful($url): void
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
        $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $em = self::getEntityManager();
        $courses = $em->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);

        // Проходим все возможные страницы GET/POST связанных с курсом
        foreach ($courses as $course) {
            self::getClient()->request('GET', $this->getPath() . '/' . $course->getId());
            $this->assertResponseOk();

            self::getClient()->request('GET', $this->getPath() . '/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            self::getClient()->request('POST', $this->getPath() . '/new');
            $this->assertResponseOk();

            self::getClient()->request('POST', $this->getPath() . '/' . $course->getId() . '/edit');
            $this->assertResponseOk();
        }

        // Проверка 404 ошибки
        $client = self::getClient();
        $url = $this->getPath() . '/13';
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    public function urlProvider(): \Generator
    {
        yield [$this->getPath() . '/'];
        yield [$this->getPath() . '/new'];
    }

    // Тесты главной страницы курсов
    public function testCourseIndex(): void
    {
        // Авторизация
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
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        //  Получаем количество курсов из БД
        $em = self::getEntityManager();
        $courses = $em->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);
        $coursesCountFromBD = count($courses);
        $coursesCount = $crawler->filter('div.card')->count();

        // Проверим кол-во курсов на странице
        self::assertEquals($coursesCountFromBD, $coursesCount);

        // Пробуем зайти на главную страницу неавторизированным
        // Выходим из учетки
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
        // Проверяем ответ
        $this->assertResponseOk();
    }

    // Тесты страницы конкретного курса
    public function testCourseShow(): void
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

        $em = self::getEntityManager();
        $courses = $em->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);

        foreach ($courses as $course) {
            if ($course->getCode() === 'DSFDFSDFDFDFFSDFSDG') {
                $crawler = self::getClient()->request('GET', $this->getPath() . '/' . $course->getId());
                $this->assertResponseOk();

                // Проверим кол-во уроков для определенного курса
                $lessonsCount = $crawler->filter('ol > li')->count();
                // Получаем кол-во уроков для курса
                $lessonsCountFromBD = count($course->getLessons());
                static::assertEquals($lessonsCountFromBD, $lessonsCount);
            } elseif ($course->getCode() === 'LFGFDGJFDJJCHJV4514') {
                self::getClient()->request('GET', $this->getPath() . '/' . $course->getId());
                self::assertResponseStatusCodeSame(500);
            }
        }
    }

        // Тест страницы добавления курса,
    public function testCourseNew(): void
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
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Создадим новый курс
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Проверяем заполнение формы
        $client->submitForm('course_save', [
            'course[code]' => 'ddfvcbgffbh',
            'course[name]' => 'Тест',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'Тестовый',
        ]);
        // Проверка редиректа
        self::assertTrue($client->getResponse()->isRedirect($this->getPath() . '/'));
        $crawler = $client->followRedirect();

        // Перейдем на страницу курса, который добавили
        $link = $crawler->filter('a.card-link')->last()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Проверим заполнение формы невалидными значениями
        // С невалидным полем code
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Переходим к форме добавления курса
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Проверка незаполнения поля code
        // Заполненяем форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => '',
            'course[name]' => 'Тест',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'Тестовый',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Code can not be empty', $error->text());

        // Проверим перезаполнение поля code (более 255 символов)
        // Заполним форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'course[name]' => 'Тест',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'Тестовый',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum code length is 255 symbols', $error->text());

        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдем к форме добавления курса
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Заполним форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => '',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'Тестовый',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Name can not be empty', $error->text());

        // Проверка перезаполнения поля name (более 255 символов)
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'Тестовый',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum name length is 255 symbols', $error->text());

        // Делаем description невалидным
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдем к форме добавления курса
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Проверка перезаполнения поля description (более 1000 символов)
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => 'Тест',
            'course[price]' => 700,
            'course[type]' => 'rent',
            'course[description]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssss
            fffffffffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssss
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkdddddddddddddddddddddddddddddddddddkfdjkjdklxmvd
            sssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkdddddddddddddddddddddddddddddddddddkfdjkjdklxmvd
            sssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkdddddddddddddddddddddddddddddddddddkfdjkjdklxmvd
            sssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkdddddddddddddddddddddddddddddddddddssssee',
        ]);

        // Ошибки
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum description length is 1000 symbols', $error->text());
    }

        // Тест страницы редактирования курса
    public function testCourseEdit(): void
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
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдем к редактированию первого курса на странице
        $link = $crawler->filter('a.card-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->filter('a.course__edit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $form = $crawler->selectButton('course_save')->form();
        $em = self::getEntityManager();
        $course = $em->getRepository(Course::class)->findOneBy(['code' => $form['course[code]']->getValue()]);
        // Изменим поля в форме
        $form['course[code]'] = 'VGKDKMFMM124';
        $form['course[name]'] = 'Пример';
        $form['course[price]'] = 1500;
        $form['course[type]'] = 'rent';
        $form['course[description]'] = 'Курс для примера';
        // Отправим форму
        $client->submit($form);

        // Проверим редирект
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
    }
}
