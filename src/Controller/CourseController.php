<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Model\CourseDto;
use App\Model\PayDto;
use App\Model\TransactionDto;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use App\Service\DecodingJwt;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="course_index", methods={"GET"})
     */
    public function index(
        CourseRepository $courseRepository,
        BillingClient $billingClient,
        DecodingJwt $decodingJwt
    ): Response {
        try {
            /** @var CourseDto[] $coursesDto */
            $coursesDto = $billingClient->getAllCourses();

            $coursesData = [];
            foreach ($coursesDto as $courseDto) {
                $coursesData[$courseDto->getCode()] = [
                    'course' => $courseDto,
                    'transaction' => null,
                ];
            }

            if (!$this->getUser()) {
                return $this->render('course/index.html.twig', [
                    'courses' => $courseRepository->findBy([], ['id' => 'ASC']),
                    'coursesData' => $coursesData,
                ]);
            }

            /** @var TransactionDto[] $transactionsDto */
            $transactionsDto = $billingClient->transactions($this->getUser(), 'type=payment&skip_expired=true');
            $coursesData = [];
            foreach ($coursesDto as $courseDto) {
                foreach ($transactionsDto as $transactionDto) {
                    if ($transactionDto->getCourseCode() === $courseDto->getCode()) {
                        $coursesData[$courseDto->getCode()] = [
                            'course' => $courseDto,
                            'transaction' => $transactionDto,
                        ];
                        break;
                    }

                    $coursesData[$courseDto->getCode()] = [
                        'course' => $courseDto,
                        'transaction' => null,
                    ];
                }
            }

            $response = $billingClient->getCurrentUser($this->getUser(), $decodingJwt);
            $data = json_decode($response, true);
            $balance = $data['balance'];

            return $this->render('course/index.html.twig', [
                'courses' => $courseRepository->findBy([], ['id' => 'ASC']),
                'coursesData' => $coursesData,
                'balance' => $balance,
            ]);
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function new(Request $request, BillingClient $billingClient): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $courseDto = new CourseDto();

                $courseDto->setTitle($form->get('name')->getData());
                $courseDto->setCode($form->get('code')->getData());
                $courseDto->setType($form->get('type')->getData());
                if ('free' === $form->get('type')->getData()) {
                    $courseDto->setPrice(0);
                } else {
                    $courseDto->setPrice($form->get('price')->getData());
                }

                $response = $billingClient->newCourse($this->getUser(), $courseDto);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($course);
                $entityManager->flush();
            } catch (BillingUnavailableException | \Exception $e) {
                return $this->render('course/new.html.twig', [
                    'course' => $course,
                    'form' => $form->createView(),
                    'errors' => $e->getMessage(),
                ]);
            }

            return $this->redirectToRoute('course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/pay", name="course_pay", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function pay(Request $request, BillingClient $billingClient): Response
    {
        $referer = $request->headers->get('referer');

        if ($referer === null) {
            return $this->redirectToRoute('course_index');
        }

        $courseCode = $request->get('course_code');
        try {
            /** @var PayDto $payDto */
            $payDto = $billingClient->paymentCourse($this->getUser(), $courseCode);
            $this->addFlash('success', 'Оплата прошла успешно!');
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }

        return $this->redirect($referer);
    }

    /**
     * @Route("/{id}", name="course_show", methods={"GET"})
     */
    public function show(Course $course, LessonRepository $lessonRepository, BillingClient $billingClient): Response
    {
        try {
            if ($this->getUser() && $this->getUser()->getRoles()[0] === 'ROLE_SUPER_ADMIN') {
                $lessons = $lessonRepository->sortLessonAsc($course);

                return $this->render('course/show.html.twig', [
                    'course' => $course,
                    'lessons' => $lessons,
                ]);
            }

            /** @var CourseDto $courseDto */
            $courseDto = $billingClient->getCourse($course->getCode());

            if ($courseDto->getType() === 'free') {
                $lessons = $lessonRepository->sortLessonAsc($course);

                return $this->render('course/show.html.twig', [
                    'course' => $course,
                    'lessons' => $lessons,
                ]);
            }

            if (!$this->getUser()) {
                throw new AccessDeniedException();
            }

            /** @var TransactionDto[] $transactionsDto */
            $transactionDto = $billingClient->transactions(
                $this->getUser(),
                'type=payment&course_code='. $course->getCode() . '&skip_expired=true'
            );

            if ($transactionDto !== []) {
                $lessons = $lessonRepository->sortLessonAsc($course);

                return $this->render('course/show.html.twig', [
                    'course' => $course,
                    'lessons' => $lessons,
                ]);
            }

            throw new AccessDeniedException('Доступ запрещен.');
        } catch (AccessDeniedException | BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @Route("/{id}/edit", name="course_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit(Request $request, Course $course): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('course_show', [
                'id' => $course->getId(),
            ]);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="course_delete", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(Request $request, Course $course): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            foreach ($course->getLessons() as $lesson) {
                $entityManager->remove($lesson);
                $entityManager->flush();
            }
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('course_index');
    }
}
