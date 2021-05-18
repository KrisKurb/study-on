<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $courseObjects = [
            // Для аренды
            [
                'code' => 'LFGFDGJFDJJCHJVHJF5',
                'name' => 'Курс испанского языка (1 уровень)',
                'description' => 'Благодаря этому курсу, вы познакомитесь с испанским языком и его основами.',
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4514',
                'name' => 'Курс испанского языка (2 уровень)',
                'description' => 'После овладения этим уровнем, вы сможете с легкостью описывать
                свои интересы и события, а главное, можно смело отправляться в путешествия!',
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4511',
                'name' => 'Курс испанского языка (3 уровень)',
                'description' => 'Курс, который подводит итог под изучением грамматики, 
                дает уверенное владение испанским. 
                На данном уровне можно без труда пройти любые собеседования и даже поступить в зарубежные ВУЗы.',
            ],
            //Бесплатные
            [
                'code' => 'JVJNJKBNDNJFDDFF444',
                'name' => 'Курс корейского языка (1 уровень)',
                'description' => 'Благодаря этому курсу, вы познакомитесь с корейским языком и его основами.',
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF445',
                'name' => 'Курс корейского языка (2 уровень)',
                'description' => 'После овладения этим уровнем, вы сможете с легкостью описывать
                свои интересы и события, а главное, можно смело отправляться в путешествия!',
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF447',
                'name' => 'Курс корейского языка (3 уровень)',
                'description' => 'Курс, который подводит итог под изучением грамматики, 
                дает уверенное владение корейским. 
                На данном уровне можно без труда пройти любые собеседования и даже поступить в зарубежные ВУЗы.',
            ],
            // Для покупки
            [
                'code' => 'DSFDFSDFDSLFLHGLHLG',
                'name' => 'Курс английского языка (1 уровень)',
                'description' => 'Благодаря этому курсу, вы познакомитесь с английским языком и его основами.',
            ],
            [
                'code' => 'DSFDJGMFKGJMDLKLDDD',
                'name' => 'Курс английского языка (2 уровень)',
                'description' => 'После овладения этим уровнем, вы сможете с легкостью описывать
                свои интересы и события, а главное, можно смело отправляться в путешествия!',
            ],
            [
                'code' => 'DSFDFSDFDFDFFSDFSDG',
                'name' => 'Курс английского языка (3 уровень)',
                'description' => 'Курс, который подводит итог под изучением грамматики, 
                дает уверенное владение английским. 
                На данном уровне можно без труда пройти любые собеседования и даже поступить в зарубежные ВУЗы.',
            ],
        ];

        $lessonObject = [
            // Для аренды
            [
                'name'=>'1.1 урок. Алфавит',
                'material'=>'Здесь мы изучим алфавит',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'1.2 урок. Произношение',
                'material'=>'Здесь мы поработаем над произношением',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.1 урок. Чтение',
                'material'=>'Здесь мы просто почитаем тексты',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.2 урок. Речь',
                'material'=>'Здесь мы поговорим',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.1 урок. Разговорный корейский',
                'material'=>'Здесь мы еще поговорим',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.2 урок. Разговорный корейский',
                'material'=>'Здесь мы еще больше поговорим',
                'number'=>random_int(1, 1000),
            ],
            //Бесплатные
            [
                'name'=>'1.1 урок. Алфавит',
                'material'=>'Здесь мы изучим алфавит',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'1.2 урок. Произношение',
                'material'=>'Здесь мы поработаем над произношением',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.1 урок. Чтение',
                'material'=>'Здесь мы просто почитаем тексты',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.2 урок. Речь',
                'material'=>'Здесь мы поговорим',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.1 урок. Разговорный корейский',
                'material'=>'Здесь мы еще поговорим',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.2 урок. Разговорный корейский',
                'material'=>'Здесь мы еще больше поговорим',
                'number'=>random_int(1, 1000),
            ],
            // Для покупки
            [
                'name'=>'1.1 урок. Английский алфавит',
                'material'=>'В алфавите у англичан всего лишь 26 букв: 5 гласных и 21 согласных.
                 Это аж на семь букв меньше, чем у нас.Двоеточие (:) показывает, что звук долгий. 
                 Выделение курсивом в букве R а:(р) означает, что в стандартном британском варианте 
                 языка буква R не произносится совсем. Например: car ≈ (ка:) автомобиль. В Америке,
                  как и в некоторых районах Англии, эта буква звучит, но не так, как наша русская Р.укву Y y можно 
                  рассматривать и как гласную, и как согласную. Поэтому прямоугольник с этой буквой наполовину красный,
                   наполовину синий. Хотя в самом начале мы её отнесли к числу согласных, вы можете считать её гласной,
                    если вам так больше нравится. Это на самом деле большого значения не играет. 
                    Важно какой звук она будет давать.',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'1.2 урок. Произношение',
                'material'=>'Удвоенные согласные произносятся как один согласный звук: hobby.
                Звонкие согласные в конце слова не становятся глухими, т.е. если написано dog.мы и произносим, не "док".
                 "Док" это "доктор", навряд ли ваша собака имеет ученую степень.',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'1.3 урок. Чтение',
                'material'=>'Здесь мы просто почитаем тексты)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.1 урок. Чтение',
                'material'=>'Здесь мы просто почитаем тексты)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.2 урок. Чтение',
                'material'=>'Здесь мы просто еще почитаем тексты)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'2.3 урок. Чтение',
                'material'=>'Здесь мы просто еще больше почитаем тексты)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.1 урок. Разговорный английский',
                'material'=>'Здесь мы поговорим)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.2 урок. Чтение',
                'material'=>'Здесь мы еще поговорим)',
                'number'=>random_int(1, 1000),
            ],
            [
                'name'=>'3.3 урок. Чтение',
                'material'=>'Здесь мы еще больше поговорим',
                'number'=>random_int(1, 1000),
            ],
            ];
        //фикстуры для курсов
        foreach ($courseObjects as $courseObj) {
            $course = new Course();
            $course->setCode($courseObj['code']);
            $course->setName($courseObj['name']);
            $course->setDescription($courseObj['description']);
            $manager->persist($course);

            //фикстуры для уроков
            //Для аренды
            if ('Курс испанского языка (1 уровень)' === $courseObj['name']) {
                for ($i=0; $i<2; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс испанского языка (2 уровень)' === $courseObj['name']) {
                for ($i=2; $i<4; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс испанского языка (3 уровень)' === $courseObj['name']) {
                for ($i=4; $i<6; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            //Бесплатные
            if ('Курс корейского языка (1 уровень)' === $courseObj['name']) {
                for ($i=6; $i<8; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс корейского языка (2 уровень)' === $courseObj['name']) {
                for ($i=8; $i<10; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс корейского языка (3 уровень)' === $courseObj['name']) {
                for ($i=10; $i<12; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            //Платные
            if ('Курс английского языка (1 уровень)' === $courseObj['name']) {
                for ($i=12; $i<15; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс английского языка (2 уровень)' === $courseObj['name']) {
                for ($i=15; $i<18; $i++) {
                    $lesson = new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
            if ('Курс английского языка (3 уровень)' === $courseObj['name']) {
                for ($i=18; $i<21; $i++) {
                    $lesson=new Lesson();
                    $lesson->setName($lessonObject[$i]['name']);
                    $lesson->setCourse($course);
                    $lesson->setMaterial($lessonObject[$i]['material']);
                    $lesson->setNumber($lessonObject[$i]['number']);
                    $manager->persist($lesson);
                }
            }
        }
        $manager->flush();
    }
}
