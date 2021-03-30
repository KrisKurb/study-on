<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;

class LessonTest extends AbstractTest
{
    // Стартовая страница курсов
    private $PageCourse = '/course';
    // Стартовая страница уроков
    private $PageLesson = '/lesson';

    // Переопределение метода для фикстур
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    // Метод вызова стартовой страницы курсов
    public function getPageCourse(): string
    {
        return $this->PageCourse;
    }

    // Метод вызова старовой страницы уроков
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
        // Эмуляция клиента
        $client = self::getClient();
        // Клиент переходит на определенный url
        $client->request('GET', $url);
        // Проверяем, что ответ от страницы успешный
        self::assertResponseIsSuccessful();

        // Проверка 404 ошибки
        $client = self::getClient();
        // Переходим по несуществующему пути
        $url = $this->getPageLesson() . '/745';
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
    public function urlProvider(): \Generator
    {
        yield [$this->getPageLesson() . '/'];
    }

    // Тест страницы добавления урока
    public function testLessonNew(): void
    {
        // Начинаем с главной страницы курсов
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдём к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполнение полей формы
        $form = $crawler->selectButton('lesson_save')->form();
        // Изменяем поля в форме
        $form['lesson[name]'] = 'Тест';
        $form['lesson[material]'] = 'Тест';
        $form['lesson[number]'] = '11';

        // Получим id созданного курса
        $course = static::getEntityManager()->getRepository(Course::class)->
        findOneBy(['id' => $form['lesson[course]']->getValue()]);
        self::assertNotEmpty($course);
        // Отправляем форму
        $client->submit($form);
        // Проверка редиректа на страницу курса
        self::assertTrue($client->getResponse()->isRedirect($this->getPageCourse() . '/' . $course->getId()));
        // Переходим на страницу добавленного урока
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Перейдём на страницу добавленного урока
        $link = $crawler->filter('ol > li > a')->first()->link();
        $client->click($link);
        $this->assertResponseOk();

        // Удалим урок
        $client->submitForm('lesson_delete');
        // Проверка редиректа на страницу курса
        self::assertTrue($client->getResponse()->isRedirect($this->getPageCourse() . '/' . $course->getId()));
        // Переходим на страницу редиректа
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Проверим заполнение формы невалидными значениями
        // Невалидное значение name
        // Страница с курсами
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдём к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Передадим пустое значение в поле name
        // Заполнение полей формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => '',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '10',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Name can not be blank', $error->text());

        // Проверка перезаполнения поля name (более 255 символов)
        // Заполнение полей формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'kfdjkjdklxmvdsssssssssssssssdsssssssssssssssssssssssssffffff
            fffffffffffffffffffffffffffffjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjsssssssssssssssssssee
            eeeeeeeeeeeeeeeeeeeeeeeeeeeeeppppppppppppppppppppppvvvvvvvvvvvvvvvvvvvvvvvvkkkkk
            kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkddddddddddddddddddddddddddddddddddd',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '10',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Name max length is 255 symbols', $error->text());

        // Тест страницы добавления урока с невалидным полем material
        // Страница с курсами
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу по ссылке
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдём к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Передадим пустое значение в поле material
        // Заполненим поля формы
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'Тест',
            'lesson[material]' => '',
            'lesson[number]' => '11',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Material field can not be empty', $error->text());

        // Тест страницы добавления урока с невалидным полем number
        // Страница с курсами
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдём к форме добавления
        $link = $crawler->filter('#lesson_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Передадим пустое значение в поле number
        // Заполненим форму
        $crawler = $client->submitForm('lesson_save', [
            'lesson[name]' => 'Тест',
            'lesson[material]' => 'Тест',
            'lesson[number]' => '',
        ]);

        // Список ошибок
        $error = $crawler->filter('span.form-error-message')->last();
        self::assertEquals('Number field can not be empty', $error->text());
    }

    // Тест страницы редактирование урока
    public function testLessonEdit(): void
    {
        // Начинаем с главной страницы с курсами
        $client = self::getClient();
        $crawler = $client->request('GET', $this->getPageCourse() . '/');
        $this->assertResponseOk();

        // Перейдём к последнему курсу
        $link = $crawler->filter('#course_select')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдём к последнему уроку
        $link = $crawler->filter('ol > li > a')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Нажмём на редактирования курса
        $link = $crawler->filter('#lesson_edit')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Заполнение полей формы
        $form = $crawler->selectButton('lesson_save')->form();
        // Получаем урок по номеру
        $lesson = self::getEntityManager()->getRepository(Lesson::class)->findOneBy([
            'number' => $form['lesson[number]']->getValue(),
            'course' => $form['lesson[course]']->getValue(),
        ]);

        // Изменяем поля в форме
        $form['lesson[name]'] = 'Test';
        $form['lesson[material]'] = 'Test';

        // Отправляем форму
        $client->submit($form);
        // Проверка редиректа на страницу урока
        self::assertTrue($client->getResponse()->isRedirect($this->getPageLesson() . '/' . $lesson->getId()));
        // Переходим на страницу редиректа
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
    }
}
