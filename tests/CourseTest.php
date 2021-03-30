<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;

class CourseTest extends AbstractTest
{

    private $base_route = '/course';

    // Переопределение метода для фикстур
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    // Метод вызова стартовой страницы курсов
    public function getPath(): string
    {
        return $this->base_route;
    }

    /**
     * @dataProvider urlProvider
     */
    //Проверяем корректно ли отображаются страницы
    public function testPageIsSuccessful($url): void
    {
        // Эмуляция клиента
        $client = self::getClient();
        // Клиент переходит на определенный url
        $client->request('GET', $url);
        // Проверяем, что ответ от страницы успешный
        self::assertResponseIsSuccessful();

        // Проверка 404 ошибки
        $client = self::getClient();
        // Переходим по несуществующему пути
        $url = $this->getPath() . '/745';
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
        $client = self::getClient();
        // Переходим на главную страницу курсов
        $crawler = $client->request('GET', $this->getPath() . '/');
        // Страница существует
        $this->assertResponseOk();

        //  Узнаем, сколько курсов в нашей БД
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        // Курсы не пустые
        self::assertNotEmpty($courses);
        $countCourses = count($courses);

        // Получение количества курсов по фильтрации класса card
        $countCoursesOfPage = $crawler->filter('div.card')->count();

        // Сравним количество в БД и количество курсов на самой странице
        self::assertEquals($countCourses, $countCoursesOfPage);
    }

    // Тесты страницы определенного курса
    public function testCourseShow(): void
    {
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);
        // Перебираем курсы
        foreach ($courses as $course) {
            $crawler = self::getClient()->request('GET', $this->getPath() . '/' . $course->getId());
            $this->assertResponseOk();

            // Получаем фактическое количество уроков для данного курса из БД
            $countLesson = count($course->getLessons());
            // Проверка количества уроков для конкретного курса
            $countLessonOfPage = $crawler->filter('ol > li')->count();

            // Проверка количества уроков в курсе
            static::assertEquals($countLesson, $countLessonOfPage);
        }
    }
    // Тест страницы добавления курса,
    public function testCourseNew(): void
    {
        // Начинаем со страницы, на которой все курсы
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Создаем новый курс
        // Получаем ссылку на новый курс
        $link = $crawler->filter('#course_new')->link();
        // Кликнули и переходим
        $client->click($link);
        $this->assertResponseOk();

        // Проверяем заполнение полей формы
        $client->submitForm('course_save', [
            'course[code]' => 'fdmndvgnfdfvdd',
            'course[name]' => 'Тест',
            'course[description]' => 'Тест',
        ]);
        // Проверка редиректа на главную страницу курсов
        self::assertTrue($client->getResponse()->isRedirect($this->getPath() . '/'));
        // Переходим на страницу редиректа
        $crawler = $client->followRedirect();

        // Перейдём на страницу добавленного курса
        // Нажимаем на последнюю ссылку (последниц добавленный курс)
        $link = $crawler->filter('#course_select')->last()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Удаляем
        // Нажимаем кнопку удалить
        $client->submitForm('course_delete');
        // Проверка редиректа на главную страницу курсов
        self::assertTrue($client->getResponse()->isRedirect($this->getPath() . '/'));
        // Переходим на страницу редиректа
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Проверяем количество курсов после удаления на главной странице
        $countCoursesOfPage = $crawler->filter('div.card')->count();
        //  Узнаем, сколько курсов в нашей БД
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);
        $countCourses = count($courses);
        // Сравниваем количество курсов на странице с количеством в БД
        self::assertEquals($countCourses, $countCoursesOfPage);

        // Проверим заполнение формы невалидными значениями
        // Тест страницы добавления курса с невалидным полем code
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
            'course[description]' => 'Тест',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->first();
        // Сравниваем ошибку, которая вывелась с той, которую мы задали
        self::assertEquals('Code can not be empty', $error->text());

        // Проверка перезаполнения поля code (более 255 символов)
        // Заполняем форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'course[name]' => 'Тест',
            'course[description]' => 'Тест',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum code length is 255 symbols', $error->text());

        // Делаем поле name невалидным
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдём к форме добавления курса
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Заполняем форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => '',
            'course[description]' => 'Тест',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Name can not be empty', $error->text());

        // Проверка перезаполнения поля name (более 255 символов)
        // Заполняем форму
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'course[description]' => 'Тест',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum name length is 255 symbols', $error->text());

        // Делаем поле description невалидным
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдём к форме добавления курса
        $link = $crawler->filter('#course_new')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Проверка перезаполнения поля description (более 1000 символов)
        // Заполнение полей формы
        $crawler = $client->submitForm('course_save', [
            'course[code]' => 'fdmnkgmkfdmkl',
            'course[name]' => 'Тест',
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

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->first();
        self::assertEquals('Maximum description length is 1000 symbols', $error->text());
    }

    // Тест страницы редактирование курса
    public function testCourseEdit(): void
    {
        // Начинаем со страницы, на которой все курсы
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPath() . '/');
        $this->assertResponseOk();

        // Перейдем к редактированию последнего курса
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Нажимаем кнопку редактирования
        $link = $crawler->filter('#course_edit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Изменим значения полей формы
        $form = $crawler->selectButton('course_save')->form();
        // Получим id кода из формы
        $course = self::getEntityManager()->getRepository(Course::class)->
        findOneBy(['code' => $form['course[code]']->getValue()]);
        // Изменяем поля в форме
        $form['course[code]'] = 'fdmnkgmkfdmkl';
        $form['course[name]'] = 'Test';
        $form['course[description]'] = 'Test';
        // Отправляем форму
        $client->submit($form);

        // Проверяем редирект на изменённый курс
        self::assertTrue($client->getResponse()->isRedirect($this->getPath() . '/' . $course->getId()));
        // Переходим на страницу редиректа
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
    }
}
